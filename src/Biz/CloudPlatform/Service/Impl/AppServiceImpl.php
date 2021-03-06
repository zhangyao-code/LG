<?php

namespace Biz\CloudPlatform\Service\Impl;

use Biz\BaseService;
use Biz\CloudPlatform\AppException;
use Biz\CloudPlatform\Client\EduSohoAppClient;
use Biz\CloudPlatform\Dao\CloudAppDao;
use Biz\CloudPlatform\Dao\CloudAppLogDao;
use Biz\CloudPlatform\Service\AppService;
use Biz\CloudPlatform\UpgradeLock;
use Biz\Common\CommonException;
use Biz\QiQiuYun\Service\QiQiuYunSdkProxyService;
use Biz\Role\Service\RoleService;
use Biz\System\Service\LogService;
use Biz\System\Service\SettingService;
use Symfony\Component\Filesystem\Filesystem;
use AppBundle\Common\ArrayToolkit;
use Topxia\Service\Common\ServiceKernel;
use Biz\Util\MySQLDumper;
use Biz\Util\PluginUtil;
use AppBundle\System;
use Biz\Crontab\SystemCrontabInitializer;

class AppServiceImpl extends BaseService implements AppService
{
    const MAX_APP_COUNT = 100;

    /**
     * @var EduSohoAppClient
     */
    private $client;

    public function getAppByCode($code)
    {
        return $this->getAppDao()->getByCode($code);
    }

    public function findApps($start, $limit)
    {
        return $this->getAppDao()->find($start, $limit);
    }

    public function findAppCount()
    {
        return $this->getAppDao()->countApps();
    }

    public function findAppsByCodes(array $codes)
    {
        $apps = $this->getAppDao()->findByCodes($codes);

        return ArrayToolkit::index($apps, 'code');
    }

    public function findAppsByTypes(array $types)
    {
        return $this->getAppDao()->findByTypes($types);
    }

    public function getCenterApps()
    {
        $apps = $this->createAppClient()->getApps();

        foreach ($apps as &$app) {
            if (null !== $app['description']) {
                $app['description'] = $this->purifyHtml($app['description']);
            }
        }

        return $apps;
    }

    public function getBinded()
    {
        return $this->createAppClient()->getBinded();
    }

    public function getCenterPackageInfo($id)
    {
        return $this->createAppClient()->getPackage($id);
    }

    public function getMainVersion()
    {
        $app = $this->getAppDao()->getByCode('MAIN');

        return $app['version'];
    }

    public function registerApp($app)
    {
        if (!ArrayToolkit::requireds($app, array('code', 'name', 'version'))) {
            $this->createNewException(CommonException::ERROR_PARAMETER_MISSING());
        }

        $app = ArrayToolkit::parts($app, array('code', 'name', 'description', 'version', 'type'));

        $app['fromVersion'] = $app['version'];
        $app['description'] = empty($app['description']) ? '' : $app['description'];
        $app['icon'] = empty($app['icon']) ? '' : $app['icon'];
        $app['developerId'] = 0;
        $app['developerName'] = empty($app['author']) ? '??????' : $app['author'];
        $app['installedTime'] = time();
        $app['updatedTime'] = time();

        $exist = $this->getAppDao()->getByCode($app['code']);

        if ($exist) {
            return $this->getAppDao()->update($exist['id'], $app);
        }

        return $this->getAppDao()->create($app);
    }

    public function checkAppUpgrades()
    {
        $mainApp = $this->getAppDao()->getByCode('MAIN');

        if (empty($mainApp)) {
            $this->addEduSohoMainApp();
        }

        $apps = $this->findApps(0, self::MAX_APP_COUNT);

        $args = array();

        foreach ($apps as $app) {
            $args[$app['code']] = $app['version'];
        }

        $lastCheck = intval($this->getSettingService()->get('_app_last_check'));

        if (empty($lastCheck) || ((time() - $lastCheck) > 86400)) {
            $this->getSettingService()->set('_app_last_check', time());
            $extInfos = array();
        } else {
            $extInfos = array('_t' => (string) time());
        }
        $apps = $this->createAppClient()->checkUpgradePackages($args, $extInfos);
        $canUpgradeApps = array_filter($apps, function ($app) {
            $userAccess = isset($app['userAccess']) ? $app['userAccess'] : null;
            $purchased = isset($app['purchased']) ? $app['purchased'] : null;

            return !('fail' == $userAccess && false == $purchased);
        });

        return $canUpgradeApps;
    }

    public function getMessages()
    {
        return $this->createAppClient()->getMessages();
    }

    public function findLogs($start, $limit)
    {
        return $this->getAppLogDao()->find($start, $limit);
    }

    public function findLogCount()
    {
        return $this->getAppLogDao()->countLogs();
    }

    protected function createPackageUpdateLog($package, $status = 'SUCCESS', $message = '')
    {
        $user = $this->getCurrentUser();
        $result = array(
            'code' => $package['product']['code'],
            'name' => $package['product']['name'],
            'fromVersion' => $package['fromVersion'],
            'toVersion' => $package['toVersion'],
            'type' => $package['type'],
            'status' => $status,
            'userId' => $user['id'],
            'ip' => $user['currentIp'],
            'message' => $message,
            'createdTime' => time(),
        );

        if ($package['backupDB']) {
            $result['dbBackupPath'] = ''; // @todo
        }

        if ($package['backupFile']) {
            $result['sourceBackupPath'] = ''; // @todo;
        }

        return $this->getAppLogDao()->create($result);
    }

    public function hasLastErrorForPackageUpdate($packageId)
    {
        $package = $this->getCenterPackageInfo($packageId);

        if (empty($package)) {
            $this->createNewException(AppException::GET_PACKAGE_FAILED());
        }

        $log = $this->getAppLogDao()->getLastLogByCodeAndToVersion($package['product']['code'], $package['toVersion']);

        if (empty($log)) {
            return false;
        }

        return 'ROLLBACK' == $log['status'];
    }

    public function checkEnvironmentForPackageUpdate($packageId)
    {
        UpgradeLock::lock();

        $errors = array();

        if (!class_exists('ZipArchive')) {
            $errors[] = 'php_zip???????????????';
        }

        if (!function_exists('curl_init')) {
            $errors[] = 'php_curl???????????????';
        }

        $filesystem = new Filesystem();

        $downloadDirectory = $this->getDownloadDirectory();

        if ($filesystem->exists($downloadDirectory)) {
            if (!is_writeable($downloadDirectory)) {
                $errors[] = sprintf('????????????(%s)????????????', $downloadDirectory);
            }
        } else {
            try {
                $filesystem->mkdir($downloadDirectory);
            } catch (\Exception $e) {
                $errors[] = sprintf('????????????(%s)????????????', $downloadDirectory);
            }
        }

        $backupdDirectory = $this->getBackUpDirectory();

        if ($filesystem->exists($backupdDirectory)) {
            if (!is_writeable($backupdDirectory)) {
                $errors[] = sprintf('??????(%s)????????????', $backupdDirectory);
            }
        } else {
            try {
                $filesystem->mkdir($backupdDirectory);
            } catch (\Exception $e) {
                $errors[] = sprintf('??????(%s)????????????', $backupdDirectory);
            }
        }

        $rootDirectory = $this->getSystemRootDirectory();

        if (!is_writeable("{$rootDirectory}/app")) {
            $errors[] = 'app??????????????????';
        }

        if (!is_writeable("{$rootDirectory}/src")) {
            $errors[] = 'src??????????????????';
        }

        if (!is_writeable("{$rootDirectory}/vendor")) {
            $errors[] = 'vendor??????????????????';
        }

        if (!is_writeable("{$rootDirectory}/plugins")) {
            $errors[] = 'plugins??????????????????';
        }

        if (!is_writeable("{$rootDirectory}/web")) {
            $errors[] = 'web??????????????????';
        }

        if (!is_writeable("{$rootDirectory}/app/cache")) {
            $errors[] = 'app/cache??????????????????';
        }

        if (!is_writeable("{$rootDirectory}/app/data")) {
            $errors[] = 'app/data??????????????????';
        }

        if (!is_writeable("{$rootDirectory}/app/config")) {
            $errors[] = 'app/config??????????????????';
        }

        if (!is_writeable("{$rootDirectory}/app/config/config.yml")) {
            $errors[] = 'app/config/config.yml??????????????????';
        }

        $package = $this->getCenterPackageInfo($packageId);

        $this->_submitRunLogForPackageUpdate('????????????', $package, $errors);

        if (!empty($errors)) {
            UpgradeLock::unlock();
        }

        return $errors;
    }

    public function checkDependsForPackageUpdate($packageId)
    {
        UpgradeLock::lock();
        $errors = array();
        $package = array('packageId' => $packageId);
        try {
            $package = $this->getCenterPackageInfo($packageId);
            // $errors  = $this->checkPluginDepend($package);
            $product = $package['product'];
            if ('MAIN' == $product['code']) {
                $app = $this->getAppByCode('MAIN');
                if ($package['fromVersion'] != $app['version']) {
                    $errors[] = sprintf('????????????(%s)???????????????????????????????????????????????????????????????', $app['version']);
                }
            }

            if (!version_compare(System::VERSION, $package['edusohoMinVersion'], '>=')) {
                $errors[] = sprintf('EduSoho?????????????????????%s??????????????????%s???????????????EduSoho', $package['edusohoMinVersion'], System::VERSION);
            }

            if ('up' != $package['edusohoMaxVersion'] && version_compare($package['edusohoMaxVersion'], System::VERSION, '<')) {
                $errors[] = sprintf('?????????????????? (%s) ???????????????????????????, ???????????????', $package['toVersion']);
            }
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }

        if (!empty($errors)) {
            UpgradeLock::unlock();
        }
        $this->_submitRunLogForPackageUpdate('????????????', $package, $errors);

        return $errors;
    }

    /**
     * ????????????????????????Edusoho?????????????????????????????????Edusoho?????????????????????.
     *
     * @param  $package
     *
     * @return array
     */
    protected function checkPluginDepend($package)
    {
        if ('MAIN' != $package['product']['code']) {
            return array();
        }
        $count = $this->getAppDao()->countApps();
        $apps = $this->getAppDao()->find(0, $count);
        $apps = ArrayToolkit::index($apps, 'code');
        $systemVersion = $apps['MAIN']['version'];
        unset($apps['MAIN']);

        $unsupportApps = array_filter($apps, function ($app) use ($systemVersion) {
            return 'up' != $app['edusohoMaxVersion'] && version_compare($app['edusohoMaxVersion'], $systemVersion, '<=');
        });

        $errors = array_map(function ($app) use ($systemVersion) {
            return "{$app['name']}????????????????????????{$app['edusohoMaxVersion']},????????????????????????";
        }, $unsupportApps);

        return $errors;
    }

    public function backupDbForPackageUpdate($packageId)
    {
        UpgradeLock::lock();
        $errors = array();
        $package = array('packageId' => $packageId);
        try {
            $package = $this->getCenterPackageInfo($packageId);
            if (empty($package)) {
                $errors[] = sprintf('???????????????#%s????????????', $packageId);
                goto last;
            }

            if (empty($package['backupDB'])) {
                goto last;
            }

            $dumper = new MySQLDumper($this->biz['db'], array(
                'exclude' => array('session', 'cache'),
            ));

            $targetBaseDir = "{$this->getBackUpDirectory()}/{$package['id']}_{$package['type']}_{$package['fromVersion']}_to_{$package['toVersion']}_db";
            $dumper->export($targetBaseDir);
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }

        last :
            if (!empty($errors)) {
                UpgradeLock::unlock();
            }

        $this->_submitRunLogForPackageUpdate('???????????????', $package, $errors);

        return $errors;
    }

    public function backupFileForPackageUpdate($packageId)
    {
        UpgradeLock::lock();
        $errors = array();
        $package = array('packageId' => $packageId);
        try {
            $filesystem = new Filesystem();
            $package = $this->getCenterPackageInfo($packageId);
            if (empty($package)) {
                $errors[] = sprintf('???????????????#%packageId%????????????', $packageId);
                goto last;
            }

            if (empty($package['backupFile'])) {
                goto last;
            }

            $targetBaseDir = "{$this->getBackUpDirectory()}/{$package['id']}_{$package['type']}_{$package['fromVersion']}_to_{$package['toVersion']}";

            if (!$filesystem->exists($targetBaseDir)) {
                $filesystem->mkdir($targetBaseDir);
            }

            $originDirs = array(
                'app/Resources',
                'app/config',
                'src',
                'web/assets',
                'web/bundles',
                'web/themes',
            );

            foreach ($originDirs as $originDir) {
                $originFullDir = $this->getSystemRootDirectory().'/'.$originDir;

                if (!$filesystem->exists($originFullDir)) {
                    continue;
                }

                $filesystem->mirror($originFullDir, $targetBaseDir.'/'.$originDir, null, array(
                    'override' => true,
                    'copy_on_windows' => true,
                ));
            }

            $originFiles = array(
                'app/AppCache.php',
                'app/AppKernel.php',
                'app/autoload.php',
                'app/bootstrap.php.cache',
                'app/console',
                'web/app.php',
            );

            foreach ($originFiles as $originFile) {
                $originFullFile = $this->getSystemRootDirectory().'/'.$originFile;

                if (!$filesystem->exists($originFullFile)) {
                    continue;
                }

                $filesystem->copy($originFullFile, $targetBaseDir.'/'.$originFile, true);
            }
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }

        last :
            if (!empty($errors)) {
                UpgradeLock::unlock();
            }
        $this->_submitRunLogForPackageUpdate('????????????', $package, $errors);

        return $errors;
    }

    public function downloadPackageForUpdate($packageId)
    {
        UpgradeLock::lock();
        $errors = array();
        $package = array('packageId' => $packageId);

        try {
            $package = $this->getCenterPackageInfo($packageId);
            if (empty($package)) {
                $this->createNewException(AppException::GET_PACKAGE_FAILED());
            }

            $filepath = $this->createAppClient()->downloadPackage($packageId);

            $this->unzipPackageFile($filepath, $this->makePackageFileUnzipDir($package));
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
        }

        if (!empty($errors)) {
            UpgradeLock::unlock();
        }

        $this->_submitRunLogForPackageUpdate('???????????????', $package, $errors);

        return $errors;
    }

    public function checkDownloadPackageForUpdate($packageId)
    {
        UpgradeLock::lock();
        $result = $this->createAppClient()->checkDownloadPackage($packageId);

        if ('ok' == $result['status']) {
            return array();
        }

        UpgradeLock::unlock();

        return $result['errors'];
    }

    public function beginPackageUpdate($packageId, $type, $index = 0)
    {
        UpgradeLock::lock();
        $errors = array();
        $package = array('packageId' => $packageId);
        try {
            $package = $this->getCenterPackageInfo($packageId);
            if (empty($package)) {
                $this->createNewException(AppException::GET_PACKAGE_FAILED());
            }

            $packageDir = $this->makePackageFileUnzipDir($package);
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
            goto last;
        }

        if (empty($index)) {
            try {
                $this->_deleteFilesForPackageUpdate($package, $packageDir);
            } catch (\Exception $e) {
                $errors[] = sprintf('?????????????????????????????????%s', $e->getMessage());
                $this->createPackageUpdateLog($package, 'ROLLBACK', implode('\n', $errors));
                goto last;
            }

            try {
                $this->_replaceFileForPackageUpdate($package, $packageDir);
            } catch (\Exception $e) {
                $errors[] = sprintf('???????????????????????????????????????%s', $e->getMessage());
                $this->createPackageUpdateLog($package, 'ROLLBACK', implode('\n', $errors));
                goto last;
            }

            try {
                $this->deleteCache();
            } catch (\Exception $e) {
                $errors[] = sprintf('????????????????????????????????????%s', $e->getMessage());
                $this->createPackageUpdateLog($package, 'ROLLBACK', implode('\n', $errors));
                goto last;
            }
        }

        try {
            $protocol = $this->tryGetProtocolFromFile($package, $packageDir);

            if ($protocol < 3) {
                $errors[] = sprintf('?????????????????? (%s) ???????????????????????????, ???????????????', $package['toVersion']);
                goto last;
            }

            $info = $this->_execScriptForPackageUpdate($package, $packageDir, $type, $index);
            $this->_submitSystemInfo($package);

            if (isset($info['index'])) {
                goto last;
            }
        } catch (\Exception $e) {
            $errors[] = sprintf('????????????/?????????????????????????????????%s', $e->getMessage());
            $this->createPackageUpdateLog($package, 'ROLLBACK', implode('\n', $errors));
            goto last;
        }

        try {
            $this->deleteCache();
        } catch (\Exception $e) {
            $cachePath = $this->biz['cache_directory'];
            $errors[] = sprintf('????????????????????????????????????????????????????????????%s?????????', $cachePath);
            $this->createPackageUpdateLog($package, 'ROLLBACK', implode('\n', $errors));
            goto last;
        }

        try {
            $this->_refreshDefaultRoles();
        } catch (\Exception $e) {
            $errors[] = '??????????????????????????????! ';
            $this->createPackageUpdateLog($package, 'ROLLBACK', implode('\n', $errors));
            goto last;
        }

        if (empty($errors)) {
            $this->updateAppForPackageUpdate($package, $packageDir);
            $this->createPackageUpdateLog($package, 'SUCCESS');
            PluginUtil::refresh();
            UpgradeLock::unlock();
        }

        last :
        $this->_submitRunLogForPackageUpdate('????????????', $package, $errors);
        $this->trySystemCrontabInitializer($package);

        if (empty($info)) {
            $result = $errors;
            UpgradeLock::unlock();
        } else {
            $result = $info;
        }

        return $result;
    }

    protected function _refreshDefaultRoles()
    {
        $this->getRoleService()->refreshRoles();
    }

    protected function deleteCache($tryCount = 0)
    {
        if ($tryCount >= 5) {
            $this->createNewException(AppException::DELETE_CACHE_FAILED());
        }

        sleep($tryCount * 2);

        try {
            $cachePath = dirname($this->biz['cache_directory']);
            $filesystem = new Filesystem();
            $filesystem->remove($cachePath);
            clearstatcache(true);
            sleep(3);
        } catch (\Exception $e) {
            ++$tryCount;
            $this->deleteCache($tryCount);
        }
    }

    public function repairProblem($token)
    {
        return $this->createAppClient()->repairProblem($token);
    }

    public function findInstallApp($code)
    {
        return $this->getAppDao()->getByCode($code);
    }

    public function uninstallApp($code)
    {
        $app = $this->getAppDao()->getByCode($code);

        if (empty($app)) {
            $this->createNewException(AppException::NOTFOUND_APP());
        }

        if ('plugin' == $app['type']) {
            $uninstallScript = realpath($this->biz['plugin.directory'].DIRECTORY_SEPARATOR.ucfirst($app['code']).'/Scripts/uninstall.php');

            if (file_exists($uninstallScript)) {
                include $uninstallScript;
                $uninstaller = new \AppUninstaller(ServiceKernel::instance());
                $uninstaller->uninstall();
            }
        } elseif ('theme' == $app['type']) {
            $themeDir = realpath($this->biz['theme.directory'].DIRECTORY_SEPARATOR.strtolower($app['code']));
            $filesystem = new Filesystem();
            $filesystem->remove($themeDir);
        }

        $this->getAppDao()->delete($app['id']);

        $cachePath = $this->biz['cache_directory'];
        $filesystem = new Filesystem();
        $filesystem->remove($cachePath);
        $this->_refreshDefaultRoles();
    }

    public function updateAppVersion($id, $version)
    {
        $app = $this->getAppDao()->get($id);

        if (empty($app)) {
            $this->createNewException(AppException::NOTFOUND_APP());
        }

        $this->getLogService()->info('system', 'update_app_version', sprintf('?????????????????????%s???????????????%s???', $app['name'], $version));

        return $this->getAppDao()->update($id, array('version' => $version));
    }

    public function getTokenLoginUrl($routingName, $params, $isSecure = false)
    {
        $appClient = $this->createAppClient(
            array(
                'isSecure' => $isSecure,
            )
        );
        $result = $appClient->getTokenLoginUrl($routingName, $params);

        return $result;
    }

    public function getAppStatusByCode($code)
    {
        return $this->createAppClient()->getAppStatusByCode($code);
    }

    protected function _replaceFileForPackageUpdate($package, $packageDir)
    {
        $filesystem = new Filesystem();
        $filesystem->mirror("{$packageDir}/source", $this->getPackageRootDirectory($package, $packageDir), null, array(
            'override' => true,
            'copy_on_windows' => true,
        ));
    }

    protected function _execScriptForPackageUpdate($package, $packageDir, $type, $index = 0)
    {
        if (file_exists($packageDir.'/EduSohoPluginUpgrade.php')) {
            include_once $packageDir.'/EduSohoPluginUpgrade.php';
            $upgrade = new \EduSohoPluginUpgrade($this->biz);
        } elseif (file_exists($packageDir.'/Upgrade.php')) {
            include_once $packageDir.'/Upgrade.php';
            $upgrade = new \EduSohoUpgrade($this->biz);
        } else {
            return;
        }

        if (method_exists($upgrade, 'setUpgradeType')) {
            $upgrade->setUpgradeType($type, $package['toVersion']);
        }

        if (method_exists($upgrade, 'update')) {
            $info = $upgrade->update($index);

            return empty($info) ? array() : $info;
        }

        return array();
    }

    private function trySystemCrontabInitializer($package)
    {
        if ('MAIN' == $package['product']['code']) {
            SystemCrontabInitializer::init();
        }
    }

    private function tryGetProtocolFromFile($package, $packageDir)
    {
        $protocol = 2;

        if ('MAIN' == $package['product']['code']) {
            return 3;
        }

        $pluginJsonFile = $packageDir.'/plugin.json';
        if (file_exists($pluginJsonFile)) {
            $meta = json_decode(file_get_contents($pluginJsonFile), true);
            $protocol = !empty($meta['protocol']) ? intval($meta['protocol']) : 2;

            return $protocol;
        }

        $themeJsonFile = $packageDir.'/theme.json';
        if (file_exists($themeJsonFile)) {
            $meta = json_decode(file_get_contents($themeJsonFile), true);
            $protocol = !empty($meta['protocol']) ? intval($meta['protocol']) : 2;

            return $protocol;
        }

        return $protocol;
    }

    protected function _deleteFilesForPackageUpdate($package, $packageDir)
    {
        if (!file_exists($packageDir.'/delete')) {
            return;
        }

        $filesystem = new Filesystem();
        $fh = fopen($packageDir.'/delete', 'r');

        while ($filepath = fgets($fh)) {
            $fullpath = $this->getPackageRootDirectory($package, $packageDir).'/'.trim($filepath);

            if (file_exists($fullpath)) {
                $filesystem->remove($fullpath);
            }
        }

        fclose($fh);
    }

    protected function _submitRunLogForPackageUpdate($message, $package, $errors)
    {
        $this->createAppClient()->submitRunLog(array(
            'level' => empty($errors) ? 'info' : 'error',
            'productId' => $package['productId'],
            'productName' => $package['product']['name'],
            'packageId' => $package['id'],
            'type' => $package['type'],
            'fromVersion' => empty($package['fromVersion']) ? '' : $package['fromVersion'],
            'toVersion' => empty($package['toVersion']) ? '' : $package['toVersion'],
            'message' => $message.(empty($errors) ? '??????' : '??????'),
            'data' => empty($errors) ? '' : json_encode($errors),
        ));
    }

    protected function _submitSystemInfo($package)
    {
        return $this->getQiQiuYunSdkProxyService()->pushEventTracking('applicationUpgrade', array(
            'productId' => $package['productId'],
            'productName' => $package['product']['name'],
            'packageId' => $package['id'],
            'fromVersion' => empty($package['fromVersion']) ? '' : $package['fromVersion'],
            'toVersion' => empty($package['toVersion']) ? '' : $package['toVersion'],
            'sysInfo' => array(
                'OS' => PHP_OS,
                'PHPVERSION' => PHP_VERSION,
            ),
        ));
    }

    protected function unzipPackageFile($filepath, $unzipDir)
    {
        $filesystem = new Filesystem();

        if ($filesystem->exists($unzipDir)) {
            $filesystem->remove($unzipDir);
        }

        $tmpUnzipDir = $unzipDir.'_tmp';

        if ($filesystem->exists($tmpUnzipDir)) {
            $filesystem->remove($tmpUnzipDir);
        }

        $filesystem->mkdir($tmpUnzipDir);

        $zip = new \ZipArchive();

        if (true === $zip->open($filepath)) {
            $tmpUnzipFullDir = $tmpUnzipDir.'/'.$zip->getNameIndex(0);
            $zip->extractTo($tmpUnzipDir);
            $zip->close();
            $filesystem->rename($tmpUnzipFullDir, $unzipDir);
            $filesystem->remove($tmpUnzipDir);
        } else {
            $this->createNewException(AppException::EXTRACT_FAILED());
        }
    }

    protected function getPackageRootDirectory($package, $packageDir)
    {
        if ('MAIN' == $package['product']['code']) {
            return $this->getSystemRootDirectory();
        }

        if (file_exists($packageDir.'/ThemeApp')) {
            return realpath($this->biz['theme.directory']);
        }

        return realpath($this->biz['plugin.directory']);
    }

    protected function getSystemRootDirectory()
    {
        return dirname($this->biz['kernel.root_dir']);
    }

    protected function getDownloadDirectory()
    {
        return $this->biz['topxia.disk.update_dir'];
    }

    protected function getBackUpDirectory()
    {
        return $this->biz['topxia.disk.backup_dir'];
    }

    protected function makePackageFileUnzipDir($package)
    {
        return $this->getDownloadDirectory().DIRECTORY_SEPARATOR.$package['fileName'];
    }

    protected function addEduSohoMainApp()
    {
        $app = array(
            'code' => 'MAIN',
            'name' => 'EduSoho?????????',
            'description' => 'EduSoho?????????',
            'icon' => '',
            'type' => AppService::CORE_TYPE,
            'version' => System::VERSION,
            'fromVersion' => '0.0.0',
            'developerId' => 1,
            'developerName' => 'EduSoho??????',
            'installedTime' => time(),
            'updatedTime' => time(),
        );
        $this->getAppDao()->create($app);
    }

    protected function updateAppForPackageUpdate($package, $packageDir)
    {
        $newApp = array(
            'code' => $package['product']['code'],
            'name' => $package['product']['name'],
            'description' => $package['product']['description'],
            'icon' => $package['product']['icon'],
            'version' => $package['toVersion'],
            'fromVersion' => $package['fromVersion'],
            'developerId' => $package['product']['developerId'],
            'developerName' => $package['product']['developerName'],
            'edusohoMaxVersion' => $package['edusohoMaxVersion'],
            'edusohoMinVersion' => $package['edusohoMinVersion'],
            'updatedTime' => time(),
        );

        $protocol = $this->tryGetProtocolFromFile($package, $packageDir);
        $newApp['protocol'] = $protocol;

        if (file_exists($packageDir.'/ThemeApp')) {
            $newApp['type'] = AppService::THEME_TYPE;
        }

        // else {
        //     $newApp['type'] = AppService::PLUGIN_TYPE;
        // }

        $app = $this->getAppDao()->getByCode($package['product']['code']);

        if (empty($app)) {
            $newApp['installedTime'] = time();

            return $this->getAppDao()->create($newApp);
        }

        return $this->getAppDao()->update($app['id'], $newApp);
    }

    /**
     * @return CloudAppDao
     */
    protected function getAppDao()
    {
        return $this->createDao('CloudPlatform:CloudAppDao');
    }

    /**
     * @return CloudAppLogDao
     */
    protected function getAppLogDao()
    {
        return $this->createDao('CloudPlatform:CloudAppLogDao');
    }

    protected function createAppClient($params = array())
    {
        if (!isset($this->client)) {
            $cloud = $this->getSettingService()->get('storage', array());
            $developer = $this->getSettingService()->get('developer', array());

            $options = array(
                'accessKey' => empty($cloud['cloud_access_key']) ? null : $cloud['cloud_access_key'],
                'secretKey' => empty($cloud['cloud_secret_key']) ? null : $cloud['cloud_secret_key'],
                'apiUrl' => empty($developer['app_api_url']) ? null : $developer['app_api_url'],
                'debug' => empty($developer['debug']) ? false : true,
                'isSecure' => empty($params['isSecure']) ? false : true,
            );

            $this->client = new EduSohoAppClient($options);
        }

        return $this->client;
    }

    /**
     * only for mock
     *
     * @param [type] $AppClient [description]
     */
    public function setAppClient($AppClient)
    {
        return $this->client = $AppClient;
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->biz->service('System:SettingService');
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->biz->service('System:LogService');
    }

    /**
     * @return RoleService
     */
    protected function getRoleService()
    {
        return $this->createService('Role:RoleService');
    }

    /**
     * @return QiQiuYunSdkProxyService
     */
    protected function getQiQiuYunSdkProxyService()
    {
        return $this->createService('QiQiuYun:QiQiuYunSdkProxyService');
    }
}
