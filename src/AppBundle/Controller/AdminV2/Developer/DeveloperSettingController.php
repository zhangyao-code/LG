<?php

namespace AppBundle\Controller\AdminV2\Developer;

use AppBundle\Common\JsonToolkit;
use AppBundle\Controller\AdminV2\BaseController;
use Biz\CloudFile\Service\CloudFileService;
use Biz\CloudPlatform\AppException;
use Biz\CloudPlatform\Service\AppService;
use Biz\File\Service\UploadFileService;
use Biz\System\Service\SettingService;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

class DeveloperSettingController extends BaseController
{
    public function indexAction(Request $request)
    {
        $developerSetting = $this->getSettingService()->get('developer', array());
        $storageSetting = $this->getSettingService()->get('storage', array());

        $default = array(
            'debug' => '0',
            'without_network' => '0',
            'cloud_api_server' => empty($storageSetting['cloud_api_server']) ? '' : $storageSetting['cloud_api_server'],
            'cloud_file_server' => '',
            'cloud_api_tui_server' => empty($storageSetting['cloud_api_tui_server']) ? '' : $storageSetting['cloud_api_tui_server'],
            'cloud_api_event_server' => empty($storageSetting['cloud_api_event_server']) ? '' : $storageSetting['cloud_api_event_server'],
            'cloud_api_es_op_server' => empty($storageSetting['cloud_api_es_op_server']) ? '' : $storageSetting['cloud_api_es_op_server'],
            'cloud_api_notification_server' => empty($storageSetting['cloud_api_notification_server']) ? '' : $storageSetting['cloud_api_notification_server'],
            'cloud_api_im_server' => '',
            'cloud_api_wechat_server' => empty($storageSetting['cloud_api_wechat_server']) ? '' : $storageSetting['cloud_api_wechat_server'],
            'app_api_url' => '',
            'cloud_sdk_cdn' => '',
            'hls_encrypted' => '1',
            'mp_service_url' => 'http://mp-service.qiqiuyun.net',
            'platform_news_api_server' => empty($storageSetting['platform_news_api_server']) ? '' : $storageSetting['platform_news_api_server'],
        );

        $developerSetting = array_merge($default, $developerSetting);

        if ('POST' == $request->getMethod()) {
            $developerSetting = $request->request->all();

            $this->openDevModeIfDebugEnable($developerSetting);

            $storageSetting['cloud_api_server'] = $developerSetting['cloud_api_server'];
            $storageSetting['cloud_api_tui_server'] = $developerSetting['cloud_api_tui_server'];
            $storageSetting['cloud_api_event_server'] = $developerSetting['cloud_api_event_server'];
            $storageSetting['cloud_api_im_server'] = $developerSetting['cloud_api_im_server'];
            $storageSetting['cloud_api_es_op_server'] = $developerSetting['cloud_api_es_op_server'];
            $storageSetting['cloud_api_notification_server'] = $developerSetting['cloud_api_notification_server'];
            $storageSetting['cloud_api_wechat_server'] = $developerSetting['cloud_api_wechat_server'];
            $storageSetting['platform_news_api_server'] = $developerSetting['platform_news_api_server'];
            $this->getSettingService()->set('storage', $storageSetting);
            $this->getSettingService()->set('developer', $developerSetting);

            $this->getLogService()->info('system', 'update_settings', '?????????????????????', $developerSetting);

            $this->dealServerConfigFile();

            $this->setFlashMessage('success', 'site.save.success');
        }

        return $this->render('admin-v2/developer/developer-setting/index.html.twig', array(
            'developerSetting' => $developerSetting,
        ));
    }

    protected function dealServerConfigFile()
    {
        $serverConfigFile = $this->getParameter('kernel.root_dir').'/data/api_server.json';
        $fileSystem = new Filesystem();
        $fileSystem->remove($serverConfigFile);
    }

    public function versionAction(Request $request)
    {
        if ('POST' == $request->getMethod()) {
            $data = $request->request->all();
            $app = $this->getAppservice()->getAppByCode($data['code']);

            if (empty($app)) {
                $this->createNewException(AppException::NOTFOUND_APP());
            }

            $this->getAppservice()->updateAppVersion($app['id'], $data['version']);

            return $this->redirect($this->generateUrl('admin_v2_app_upgrades'));
        }

        $appCount = $this->getAppservice()->findAppCount();
        $apps = $this->getAppservice()->findApps(0, $appCount);

        return $this->render('admin-v2/developer/developer-setting/version.html.twig', array(
            'apps' => $apps,
        ));
    }

    public function magicAction(Request $request)
    {
        if ('POST' == $request->getMethod()) {
            $setting = $request->request->get('setting', '{}');
            $setting = json_decode($setting, true);

            if (empty($setting)) {
                $setting = array('export_allow_count' => 100000, 'export_limit' => 10000, 'enable_org' => 0);
            }

            $this->getSettingService()->set('magic', $setting);
            $this->getLogService()->info('system', 'update_settings', '??????Magic??????', $setting);
            $this->setFlashMessage('success', 'site.save.success');
        }

        $setting = $this->getSettingService()->get('magic', array());
        $setting = JsonToolkit::prettyPrint(json_encode($setting));

        return $this->render('admin-v2/developer/developer-setting/magic.html.twig', array(
            'setting' => $setting,
        ));
    }

    private function openDevModeIfDebugEnable($developerSetting)
    {
        try {
            $fileSystem = new Filesystem();
            $devLockFile = $this->container->getParameter('kernel.root_dir').'/data/dev.lock';
            $ignoreDeleteDevLockFile = $this->container->getParameter('kernel.root_dir').'/data/ignoreDeleteDevLock';
            if ($developerSetting['debug']) {
                $fileSystem->touch($devLockFile);
            } else {
                if (!$fileSystem->exists($ignoreDeleteDevLockFile)) {
                    $fileSystem->remove($devLockFile);
                }
            }
        } catch (\Exception $e) {
            //?????????????????????dev.lock?????????????????????????????????????????????
            //??????????????????????????????????????????????????????
        }
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    /**
     * @return AppService
     */
    protected function getAppService()
    {
        return $this->createService('CloudPlatform:AppService');
    }

    /**
     * @return CloudFileService
     */
    protected function getCloudFileService()
    {
        return $this->createService('CloudFile:CloudFileService');
    }

    /**
     * @return UploadFileService
     */
    protected function getUploadFileService()
    {
        return $this->createService('File:UploadFileService');
    }
}
