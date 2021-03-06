<?php

namespace AppBundle\Common;

use Biz\Content\Service\BlockService;
use Biz\Content\Service\ContentService;
use Biz\Content\Service\FileService;
use Biz\Content\Service\NavigationService;
use Biz\Crontab\SystemCrontabInitializer;
use Biz\Dictionary\Service\DictionaryService;
use Biz\Org\Service\OrgService;
use Biz\Role\Service\RoleService;
use Biz\System\Service\SettingService;
use Biz\Taxonomy\Service\CategoryService;
use Biz\Taxonomy\Service\TagService;
use Biz\User\CurrentUser;
use Biz\User\Service\UserService;
use Codeages\Biz\Pay\Service\AccountService;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Topxia\Service\Common\ServiceKernel;
use CustomBundle\Biz\Common\CustomSystemInitializer;

class SystemInitializer
{
    protected $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function init()
    {
        $this->_initTag();
        $this->_initCategory();
        $this->_initFile();
        $this->_initPages();
        $this->_initNavigations();
        $this->_initBlocks();
        $this->_initThemes();
        $this->_initCoin();
        $this->_initJob();
        $this->_initQueueJob();
        $this->_initOrg();
        $this->_initRole();
        $this->_initUserBalance();

        $this->_initDefaultSetting();
        $this->_initMagicSetting();
        $this->_initMailerSetting();
        $this->_initConsultSetting();
        $this->_initPaymentSetting();
        $this->_initRefundSetting();
        $this->_initSiteSetting();
        $this->_initStorageSetting();
        $this->_initCouponSetting();
        $this->_initQuestionBankCategory();
        $this->_initSystemUsers();
        $this->_initCustom();
    }

    public function _initCustom()
    {
        try {
            $biz = ServiceKernel::instance()->getBiz();
            $customSystemInitializer = new CustomSystemInitializer($biz, $this->output);
            $customSystemInitializer->init();
        } catch (\Exception $e) {
            $this->output->write('  ??????????????????????????????'.$e->getMessage());
        }
    }

    public function initAdminUser($fields)
    {
        $this->output->write("  ?????????????????????:{$fields['email']}, ?????????{$fields['password']}   ");
        $fields['emailVerified'] = 1;

        $user = $this->getUserService()->getUserByEmail($fields['email']);

        if (empty($user)) {
            $user = $this->getUserService()->register($fields);
        }

        $user['roles'] = array('ROLE_USER', 'ROLE_TEACHER', 'ROLE_SUPER_ADMIN');
        $user['currentIp'] = '127.0.0.1';

        $currentUser = new CurrentUser();
        $currentUser->fromArray($user);
        ServiceKernel::instance()->setCurrentUser($currentUser);

        $this->getUserService()->changeUserRoles($user['id'], array('ROLE_USER', 'ROLE_TEACHER', 'ROLE_SUPER_ADMIN'));

        $this->output->writeln(' ...<info>??????</info>');

        return $this->getUserService()->getUser($user['id']);
    }

    private function _initMailerSetting()
    {
        $this->output->write('  ??????????????????????????????');

        $default = array(
            'enabled' => 0,
            'host' => 'smtp.exmail.qq.com',
            'port' => '25',
            'username' => 'user@example.com',
            'password' => '',
            'from' => 'user@example.com',
            'name' => '',
        );
        $this->getSettingService()->set('mailer', $default);

        $this->output->writeln(' ...<info>??????</info>');
    }

    private function _initMagicSetting()
    {
        $this->output->write('  ?????????magic??????');
        $default = array(
            'export_allow_count' => 100000,
            'export_limit' => 10000,
            'enable_org' => 0,
        );

        $this->getSettingService()->set('magic', $default);

        $this->output->writeln(' ...<info>??????</info>');
    }

    private function _initConsultSetting()
    {
        $this->output->write('  ?????????????????????');

        $default = array(
            'enabled' => 0,
            'worktime' => '9:00 - 17:00',
            'qq' => array(
                array('name' => '', 'number' => ''),
            ),
            'qqgroup' => array(
                array('name' => '', 'number' => ''),
            ),
            'phone' => array(
                array('name' => '', 'number' => ''),
            ),
            'webchatURI' => '',
            'email' => '',
            'color' => 'default',
        );

        $this->getSettingService()->set('contact', $default);

        $this->output->writeln(' ...<info>??????</info>');
    }

    private function _initRefundSetting()
    {
        $this->output->write('  ?????????????????????');

        $setting = array(
            'maxRefundDays' => 10,
            'applyNotification' => '?????????????????????{{item}}??????????????????????????????????????????????????????????????????????????????',
            'successNotification' => '???????????????????????????{{item}} ??????????????????????????????{{amount}}??????',
            'failedNotification' => '???????????????????????????{{item}} ?????????????????????????????????????????????????????????',
        );
        $this->getSettingService()->set('refund', $setting);
        $this->output->writeln(' ...<info>??????</info>');
    }

    private function _initSiteSetting()
    {
        $this->output->write('  ?????????????????????');

        $default = array(
            'name' => '????????????',
            'slogan' => '?????????????????????????????????',
            'url' => '',
            'logo' => '',
            'seo_keywords' => '????????????, ????????????????????????',
            'seo_description' => '',
            'master_email' => 'test@edusoho.com',
            'icp' => ' ???ICP???13006852???-1',
            'analytics' => '',
            'status' => 'open',
            'closed_note' => '',
        );

        $this->getSettingService()->set('site', $default);
        $this->output->writeln(' ...<info>??????</info>');
    }

    private function _initPaymentSetting()
    {
        $this->output->write('  ?????????????????????');

        $default = array(
            'enabled' => 0,
            'bank_gateway' => 'none',
            'alipay_enabled' => 0,
            'alipay_key' => '',
            'alipay_accessKey' => '',
            'alipay_secretKey' => '',
        );

        $this->getSettingService()->set('payment', $default);

        $this->output->writeln(' ...<info>??????</info>');
    }

    private function _initDefaultSetting()
    {
        $this->output->write('  ??????????????????????????????');
        $settingService = $this->getSettingService();

        $defaultSetting = array();
        $defaultSetting['chapter_name'] = '???';
        $defaultSetting['user_name'] = '??????';
        $defaultSetting['part_name'] = '???';

        $default = $settingService->get('default', array());
        $defaultSetting = array_merge($default, $defaultSetting);

        $settingService->set('default', $defaultSetting);

        $setting = array(
            'rules' => array(
                'thread' => array(
                    'fiveMuniteRule' => array(
                        'interval' => 300,
                        'postNum' => 100,
                    ),
                ),
                'threadLoginedUser' => array(
                    'fiveMuniteRule' => array(
                        'interval' => 300,
                        'postNum' => 50,
                    ),
                ),
            ),
        );
        $settingService->set('post_num_rules', $setting);

        $settingService->get('developer', array());
        $developer['cloud_api_failover'] = 1;
        $settingService->set('developer', $developer);
        $settingService->set('backstage', array('is_v2' => 1));
        $this->output->writeln(' ...<info>??????</info>');
    }

    private function _initStorageSetting()
    {
        $this->output->write('  ???????????????????????????');

        $default = array(
            'upload_mode' => 'local',
            'cloud_api_server' => 'http://api.edusoho.net',
            'cloud_access_key' => '',
            'cloud_secret_key' => '',
            'video_h5_enable' => 1,
        );

        $this->getSettingService()->set('storage', $default);

        $this->output->writeln(' ...<info>??????</info>');
    }

    private function _initCouponSetting()
    {
        $this->output->write('  ????????????????????????');

        $default = array(
            'enabled' => 1,
        );

        $this->getSettingService()->set('coupon', $default);

        $this->output->writeln(' ...<info>??????</info>');
    }

    public function initRegisterSetting($user)
    {
        $this->output->write('  ?????????????????????');

        $emailBody = <<<'EOD'
Hi, {{nickname}}

????????????{{sitename}}!

???????????????????????????????????????

{{verifyurl}}

??????????????????????????????????????????????????????????????????????????????(???IE)???????????????????????????????????????24????????????????????????

?????????{{sitename}}????????????

{{sitename}} {{siteurl}}

(???????????????????????????email??????????????????)
EOD;

        $default = array(
            'register_mode' => 'email',
            'email_activation_title' => '???????????????{{sitename}}??????',
            'email_activation_body' => trim($emailBody),
            'welcome_enabled' => 'opened',
            'welcome_sender' => $user['nickname'],
            'welcome_methods' => array(),
            'welcome_title' => '????????????{{sitename}}',
            'welcome_body' => '??????{{nickname}}?????????{{sitename}}???????????????????????????{{sitename}}????????????????????????????????????????????????????????????',
        );

        $this->getSettingService()->set('auth', $default);

        $this->output->writeln(' ...<info>??????</info>');
    }

    protected function _initTag()
    {
        $this->output->write('  ???????????????');
        $defaultTag = $this->getTagService()->getTagByName('????????????');

        if (!$defaultTag) {
            $this->getTagService()->addTag(array('name' => '????????????'));
        }

        $this->output->writeln(' ...<info>??????</info>');
    }

    protected function _initCategory()
    {
        $this->output->write('  ?????????????????????');

        $courseGroup = $this->getCategoryService()->getGroupByCode('course');

        if (empty($courseGroup)) {
            $courseGroup = $this->getCategoryService()->addGroup(array(
                'name' => '????????????',
                'code' => 'course',
                'depth' => 3,
            ));
        }

        $courseCategory = $this->getCategoryService()->getCategoryByCode('default');

        if (empty($courseCategory)) {
            $this->getCategoryService()->createCategory(array(
                'name' => '????????????',
                'code' => 'default',
                'weight' => 100,
                'groupId' => $courseGroup['id'],
                'parentId' => 0,
            ));
        }

        $classroomGroup = $this->getCategoryService()->getGroupByCode('classroom');

        if (!$classroomGroup) {
            $classroomGroup = $this->getCategoryService()->addGroup(array(
                'name' => '????????????',
                'code' => 'classroom',
                'depth' => 3,
            ));
        }

        $classroomCategory = $this->getCategoryService()->getCategoryByCode('classroomdefault');

        if (!$classroomCategory) {
            $this->getCategoryService()->createCategory(array(
                'name' => '????????????',
                'code' => 'classroomdefault',
                'weight' => 100,
                'groupId' => $classroomGroup['id'],
                'parentId' => 0,
            ));
        }

        $this->output->writeln(' ...<info>??????</info>');
    }

    protected function _initFile()
    {
        $this->output->write('  ?????????????????????');

        $groups = $this->getFileService()->getAllFileGroups();

        foreach ($groups as $group) {
            $this->getFileService()->deleteFileGroup($group['id']);
        }

        $this->getFileService()->addFileGroup(array(
            'name' => '???????????????',
            'code' => 'default',
            'public' => 1,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '?????????',
            'code' => 'thumb',
            'public' => 1,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '??????',
            'code' => 'course',
            'public' => 1,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '??????',
            'code' => 'user',
            'public' => 1,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '??????????????????',
            'code' => 'course_private',
            'public' => 0,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '??????',
            'code' => 'article',
            'public' => 1,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '????????????',
            'code' => 'tmp',
            'public' => 1,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '??????????????????',
            'code' => 'system',
            'public' => 1,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '??????',
            'code' => 'group',
            'public' => 1,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '?????????',
            'code' => 'block',
            'public' => 1,
        ));

        $this->getFileService()->addFileGroup(array(
            'name' => '??????',
            'code' => 'classroom',
            'public' => 1,
        ));

        $this->output->writeln(' ...<info>??????</info>');
    }

    protected function _initPages()
    {
    }

    protected function _initCoin()
    {
        $this->output->write('  ??????????????????');

        $default = array(
            'cash_model' => 'none',
            'cash_rate' => 1,
            'coin_enabled' => 0,
        );

        $this->getSettingService()->set('coin', $default);

        $this->output->writeln(' ...<info>??????</info>');
    }

    protected function _initNavigations()
    {
        $this->output->write('  ???????????????');

        $this->getNavigationService()->createNavigation(array(
            'name' => '????????????',
            'url' => 'teacher',
            'sequence' => 1,
            'isNewWin' => 0,
            'isOpen' => 1,
            'type' => 'top',
        ));

        $this->getNavigationService()->createNavigation(array(
            'name' => '????????????',
            'url' => 'page/questions',
            'sequence' => 2,
            'isNewWin' => 0,
            'isOpen' => 1,
            'type' => 'top',
        ));

        $this->getNavigationService()->createNavigation(array(
            'name' => '????????????',
            'url' => 'page/aboutus',
            'sequence' => 2,
            'isNewWin' => 0,
            'isOpen' => 1,
            'type' => 'top',
        ));

        $this->output->writeln(' ...<info>??????</info>');
    }

    protected function _initThemes()
    {
        $this->getSettingService()->set('theme', array('uri' => 'jianmo'));
    }

    protected function _initBlocks()
    {
        $themeDir = ServiceKernel::instance()->getParameter('kernel.root_dir').DIRECTORY_SEPARATOR.'../web/themes';
        $this->output->write('  ??????????????????');

        $metaFiles = array(
            'system' => "{$themeDir}/block.json",
            'default' => "{$themeDir}/default/block.json",
            'autumn' => "{$themeDir}/autumn/block.json",
            'jianmo' => "{$themeDir}/jianmo/block.json",
        );

        foreach ($metaFiles as $category => $file) {
            $metas = file_get_contents($file);
            $metas = json_decode($metas, true);

            foreach ($metas as $code => $meta) {
                $data = array();

                foreach ($meta['items'] as $key => $item) {
                    $data[$key] = $item['default'];
                }

                $filename = __DIR__.'/blocks/'.'block-'.md5($code).'.html';

                if (file_exists($filename)) {
                    $content = file_get_contents($filename);
                    $content = preg_replace_callback('/(<img[^>]+>)/i', function ($matches) {
                        preg_match_all('/<\s*img[^>]*src\s*=\s*["\']?([^"\']*)/is', $matches[0], $srcs);
                        preg_match_all('/<\s*img[^>]*alt\s*=\s*["\']?([^"\']*)/is', $matches[0], $alts);
                        $URI = preg_replace('/'.INSTALL_URI.'.*/i', '', $_SERVER['REQUEST_URI']);
                        $src = preg_replace('/\b\?[\d]+.[\d]+.[\d]+/i', '', $srcs[1][0]);
                        $src = $URI.trim($src);

                        $img = "<img src='{$src}'";

                        if (isset($alts[1][0])) {
                            $alt = $alts[1][0];
                            $img .= " alt='{$alt}'>";
                        } else {
                            $img .= '>';
                        }

                        return $img;
                    }, $content);
                } else {
                    $content = '';
                }
                $blockTemplate = $this->getBlockService()->getBlockTemplateByCode($code);
                if (empty($blockTemplate)) {
                    $blockTemplate = $this->getBlockService()->createBlockTemplate(array(
                        'title' => $meta['title'],
                        'mode' => 'template',
                        'templateName' => $meta['templateName'],
                        'content' => $content,
                        'code' => $code,
                        'meta' => $meta,
                        'data' => $data,
                        'category' => $category,
                    ));
                } else {
                    $blockTemplate = $this->getBlockService()->updateBlockTemplate($blockTemplate['id'], array(
                        'mode' => 'template',
                        'category' => empty($meta['category']) ? 'system' : $meta['category'],
                        'meta' => $meta,
                        'data' => $data,
                        'content' => $content,
                        'templateName' => $meta['templateName'],
                        'title' => $meta['title'],
                    ));
                }

                $block = $this->getBlockService()->getBlockByCode($code);
                if (empty($block)) {
                    $this->getBlockService()->createBlock(array(
                        'blockTemplateId' => $blockTemplate['id'],
                        'code' => $code,
                        'content' => $content,
                        'data' => $data,
                    ));
                } else {
                    $this->getBlockService()->updateBlockTemplate($block['blockTemplateId'], array(
                        'content' => $content,
                        'data' => $data,
                    ));
                }
            }
        }

        $this->output->writeln(' ...<info>??????</info>');
    }

    protected function _initJob()
    {
        $this->output->write('  ?????????CrontabJob');

        SystemCrontabInitializer::init();

        $this->output->writeln(' ...<info>??????</info>');
    }

    protected function _initQueueJob()
    {
        $this->output->write('  DataBase?????????????????????');
        try {
            SystemQueueCrontabinitializer::init();
            $this->output->writeln(' ...<info>??????</info>');
        } catch (\Exception $e) {
            $this->output->writeln(' ...<info>??????</info>'.$e->getMessage());
        }
    }

    protected function _initSystemUsers()
    {
        $this->getUserService()->initSystemUsers();
    }

    protected function _initOrg()
    {
        $org = $this->getOrgService()->getOrgByCode('FullSite');

        if (!empty($org)) {
            return;
        }

        $org = array(
            'name' => '??????',
            'code' => 'FullSite',
        );

        $this->getOrgService()->createOrg($org);
    }

    public function initFolders()
    {
        $folders = array(
            ServiceKernel::instance()->getParameter('kernel.root_dir').'/data/udisk',
            ServiceKernel::instance()->getParameter('kernel.root_dir').'/data/private_files',
            ServiceKernel::instance()->getParameter('kernel.root_dir').'/../web/files',
        );

        $filesystem = new Filesystem();

        foreach ($folders as $folder) {
            if (!$filesystem->exists($folder)) {
                $filesystem->mkdir($folder);
            }
        }
    }

    protected function _initRole()
    {
        $this->output->write('  ???????????????');
        $this->getRoleService()->refreshRoles();
        $this->output->writeln(' ...<info>??????</info>');
    }

    protected function _initQuestionBankCategory()
    {
        $this->output->write('  ???????????????????????????');
        $this->getQuestionBankCategoryService()->createCategory(array('name' => '????????????', 'parentId' => 0));
        $this->output->writeln(' ...<info>??????</info>');
    }

    public function initLockFile()
    {
        $this->output->write('  ?????????install.lock');
        touch(ServiceKernel::instance()->getParameter('kernel.root_dir').'/data/install.lock');
        touch(ServiceKernel::instance()->getParameter('kernel.root_dir').'/config/routing_plugins.yml');

        $this->output->writeln(' ...<info>??????</info>');
    }

    /**
     * ??????????????????
     */
    private function _initUserBalance()
    {
        $this->getAccountService()->createUserBalance(array('user_id' => 0));
    }

    /**
     * @return TagService
     */
    protected function getTagService()
    {
        return ServiceKernel::instance()->getBiz()->service('Taxonomy:TagService');
    }

    /**
     * @return AccountService
     */
    protected function getAccountService()
    {
        return ServiceKernel::instance()->getBiz()->service('Pay:AccountService');
    }

    /**
     * @return CategoryService
     */
    protected function getCategoryService()
    {
        return ServiceKernel::instance()->getBiz()->service('Taxonomy:CategoryService');
    }

    /**
     * @return UserService
     */
    private function getUserService()
    {
        return ServiceKernel::instance()->getBiz()->service('User:UserService');
    }

    /**
     * @return SettingService
     */
    private function getSettingService()
    {
        return ServiceKernel::instance()->getBiz()->service('System:SettingService');
    }

    /**
     * @return FileService
     */
    private function getFileService()
    {
        return ServiceKernel::instance()->getBiz()->service('Content:FileService');
    }

    /**
     * @return ContentService
     */
    protected function getContentService()
    {
        return ServiceKernel::instance()->getBiz()->service('Content:ContentService');
    }

    /**
     * @return BlockService
     */
    protected function getBlockService()
    {
        return ServiceKernel::instance()->getBiz()->service('Content:BlockService');
    }

    /**
     * @return NavigationService
     */
    protected function getNavigationService()
    {
        return ServiceKernel::instance()->getBiz()->service('Content:NavigationService');
    }

    /**
     * @return OrgService
     */
    protected function getOrgService()
    {
        return ServiceKernel::instance()->getBiz()->service('Org:OrgService');
    }

    /**
     * @return RoleService
     */
    protected function getRoleService()
    {
        return ServiceKernel::instance()->getBiz()->service('Role:RoleService');
    }

    /**
     * @return DictionaryService
     */
    protected function getDictionaryService()
    {
        return ServiceKernel::instance()->getBiz()->service('Dictionary:DictionaryService');
    }

    /**
     * @return \Biz\QuestionBank\Service\CategoryService
     */
    protected function getQuestionBankCategoryService()
    {
        return ServiceKernel::instance()->getBiz()->service('QuestionBank:CategoryService');
    }
}
