<?php

namespace Biz;

use Biz\UnitTests\DatabaseDataClearer;
use Codeages\PluginBundle\Event\LazyDispatcher;
use CustomBundle\Biz\CustomServiceProvider;
use Mockery;
use Biz\User\CurrentUser;
use Biz\Role\Util\PermissionBuilder;
use Codeages\Biz\Framework\Context\Biz;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\Event;
use Topxia\Service\Common\ServiceKernel;
use PHPUnit\Framework\TestCase;
use Biz\TestTool\MockedRequest;
use AppBundle\Common\ArrayToolkit;

class BaseTestCase extends TestCase
{
    /** @var $appKernel \AppKernel */
    protected static $appKernel;

    /**
     * @var Biz
     */
    protected $biz;

    /** @var $db \Doctrine\DBAL\Connection */
    protected static $db;

    /** @var $redis \Redis */
    protected static $redis = null;

    public static function setDb($db)
    {
        self::$db = $db;
    }

    public static function setRedis($redis)
    {
        self::$redis = $redis;
    }

    public static function setAppKernel(\AppKernel $appKernel)
    {
        self::$appKernel = $appKernel;
    }

    public function emptyDatabaseQuickly()
    {
        $clear = new DatabaseDataClearer(self::$db);
        $clear->clearQuickly();
    }

    protected function createService($alias)
    {
        return $this->getBiz()->service($alias);
    }

    protected function createDao($alias)
    {
        return $this->getBiz()->Dao($alias);
    }

    /**
     * @return CurrentUser
     */
    protected function getCurrentUser()
    {
        return $this->biz['user'];
    }

    public function getServiceKernel()
    {
        return ServiceKernel::instance();
    }

    public static function setUpBeforeClass()
    {
        $_SERVER['HTTP_HOST'] = 'test.com'; //mock $_SERVER['HTTP_HOST'] for http request testing
    }

    public function setUp()
    {
        $this->initBiz();
        $this->emptyDatabaseQuickly();
        self::$db->beginTransaction();
        if (self::$redis) {
            self::$redis->flushDb();
        }

        $this
            ->initDevelopSetting()
            ->initCurrentUser();
    }

    /**
     * @return Biz
     */
    protected function initBiz()
    {
        $container = self::$appKernel->getContainer();
        $oldBiz = $container->get('biz');
        $biz = new Biz($container->getParameter('biz_config'));
        self::$appKernel->initializeBiz($biz);
        $biz['db'] = self::$db;
        $biz['redis'] = self::$redis;
        $biz['migration.directories'] = $oldBiz['migration.directories'];
        $biz['autoload.aliases'] = $oldBiz['autoload.aliases'];
        $biz->register(new CustomServiceProvider());

        $this->biz = $biz;
        $biz['dispatcher'] = function () use ($container, $biz) {
            $dispatcher = new TestCaseLazyDispatcher($container);
            $dispatcher->setBiz($biz);

            return $dispatcher;
        };

        return $biz;
    }

    public function tearDown()
    {
        $biz = $this->getBiz();
        $biz['db']->rollback();
    }

    protected function initDevelopSetting()
    {
        $this->getServiceKernel()->createService('System:SettingService')->set('developer', array(
            'without_network' => '1',
        ));

        return $this;
    }

    protected function initCurrentUser()
    {
        /** @var $userService \Biz\User\Service\UserService */
        $userService = $this->createService('User:UserService');

        $currentUser = new CurrentUser();
        //????????????????????????????????????????????????CurrentUser???????????????????????????????????????register?????????mock?????????????????????????????????CurrentUser
        $currentUser->fromArray(array(
            'id' => 0,
            'nickname' => '??????',
            'currentIp' => '127.0.0.1',
            'roles' => array('ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN', 'ROLE_TEACHER'),
            'org' => array('id' => 1, 'orgCode' => '1.'),
        ));

        $this->getServiceKernel()->setBiz($this->getBiz());
        $this->getServiceKernel()->setCurrentUser($currentUser);

        $user = $userService->register(array(
            'nickname' => 'admin',
            'email' => 'admin@admin.com',
            'password' => 'admin',
            'createdIp' => '127.0.0.1',
            'orgCode' => '1.',
            'orgId' => '1',
        ));
        $roles = array('ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN', 'ROLE_TEACHER');
        $user = $userService->changeUserRoles($user['id'], $roles);
        $user['currentIp'] = $user['createdIp'];
        $user['org'] = array('id' => 1, 'orgCode' => '1.');
        $currentUser = new CurrentUser();
        $currentUser->fromArray($user);
        $this->grantPermissionToUser($currentUser);
        $this->getServiceKernel()->setCurrentUser($currentUser);
        $this->getServiceKernel()->createService('Role:RoleService')->refreshRoles();
        $this->getServiceKernel()->getCurrentUser()->setPermissions(PermissionBuilder::instance()->getPermissionsByRoles($currentUser->getRoles()));

        $biz = $this->getBiz();
        $biz['user'] = $currentUser;

        $container = self::$appKernel->getContainer();
        $singletonBiz = $container->get('biz');
        $singletonBiz['user'] = $currentUser;

        return $this;
    }

    /**
     * ?????? mock???service?????????dao
     * ??????$this->mockBiz(
     *      'Course:CourseService',
     *       array(
     *          array(
     *              'functionName' => 'tryManageCourse',
     *              'returnValue' => array('id' => 1),
     *          ),
     *      )
     *  );
     * ???param $alias  createService?????????createDao ??????????????????
     * ???param $params ????????????
     *  array(
     *      array(
     *          'functionName' => 'tryManageCourse',???//??????
     *          'returnValue' => array('id' => 1),???// ????????????????????????????????????????????????
     *          'throwException' => new \Exception(), //object Exception or string Exception ??????returnValue ????????????????????????throwException??????
     *          'withParams' => array('param1', array('arrayParamKey1' => '123')),???
     *                          //???????????????????????????????????????????????????????????????
     *                          //????????????????????????????????????
     *          'runTimes' => 1 //????????????????????????????????????????????????, ????????????????????????????????????????????????
     *      )
     *  )
     *
     * @return \Mockery\MockInterface
     */
    protected function mockBiz($alias, $params = array())
    {
        $mockedObj = $this->mockObject($alias, $params);

        $biz = $this->getBiz();
        $biz['@'.$alias] = $mockedObj;

        return $mockedObj;
    }

    protected function mockPureBiz($alias, $params = array())
    {
        $mockedObj = $this->mockObject($alias, $params);

        $biz = $this->getBiz();
        $biz[$alias] = $mockedObj;

        return $mockedObj;
    }

    /**
     * @param $config ??????
     *   array(
     *      'request' => array(
     *          'username' => 'aok',
     *          'password' => 'dds',   //$request->request->get('password') ????????? dss
     *      )
     *   )
     */
    protected function mockRequest($configs)
    {
        return MockedRequest::mockRequest($configs);
    }

    protected static function getContainer()
    {
        global $kernel;

        return $kernel->getContainer();
    }

    /**
     * @return Biz
     */
    protected function getBiz()
    {
        return $this->biz;
    }

    /**
     * @param $uniqueCols ??????????????????????????????????????????????????????????????????????????????????????? ????????????????????????
     *   ??? array('id'), ??????????????? ??? id ???????????????????????????
     *   ????????? ??????????????????????????????
     */
    protected function assertArrayEquals(array $arr1, array $arr2, array $keyAry = array(), $uniqueCols = array())
    {
        if (count($keyAry) >= 1) {
            foreach ($keyAry as $key) {
                $this->assertEquals($arr1[$key], $arr2[$key]);
            }
        } else {
            if (!empty($uniqueCols)) {
                $formatedArr1 = ArrayToolkit::index($arr1, $uniqueCols[0]);
                $formatedArr2 = ArrayToolkit::index($arr2, $uniqueCols[0]);
            } else {
                $formatedArr1 = $arr1;
                $formatedArr2 = $arr2;
            }

            foreach (array_keys($formatedArr1) as $key) {
                if (is_array($formatedArr1[$key])) {
                    $this->assertArrayEquals($formatedArr1[$key], $formatedArr2[$key]);
                } else {
                    $this->assertEquals($formatedArr1[$key], $formatedArr2[$key]);
                }
            }
        }
    }

    protected function assertArrayValueEquals(array $arr1, array $arr2)
    {
        foreach ($arr1 as $value) {
            $this->assertTrue(in_array($value, $arr2));
        }
    }

    protected function assertArraySternEquals(array $ary1, array $ary2)
    {
        foreach ($ary1 as $key => $item) {
            $this->assertEquals($item, $ary2[$key]);
        }
    }

    protected function grantPermissionToUser($currentUser)
    {
        $permissions = new \ArrayObject();
        $permissions['admin_course_content_manage'] = true;
        /* @var $currentUser CurrentUser */
        $currentUser->setPermissions($permissions);
    }

    protected function mockObject($alias, $params = array())
    {
        $splitedChars = array(':', '.');
        $className = $alias;
        foreach ($splitedChars as $char) {
            $aliasList = explode($char, $className);
            $className = end($aliasList);
        }

        $mockObj = Mockery::mock($className);

        foreach ($params as $param) {
            $expectation = $mockObj->shouldReceive($param['functionName']);

            if (isset($param['runTimes'])) {
                $expectation = $expectation->times($param['runTimes']);
            }

            if (!empty($param['withParams'])) {
                $expectation = $expectation->withArgs($param['withParams']);
            } else {
                $expectation = $expectation->withAnyArgs();
            }

            if (isset($param['returnValue'])) {
                $expectation->andReturn($param['returnValue']);
            }

            if (isset($param['andReturnValues'])) {
                $expectation->andReturnValues($param['andReturnValues']);
            }

            if (isset($param['throwException'])) {
                $expectation->andThrow($param['throwException']);
            }
        }

        return $mockObj;
    }
}

class TestCaseLazyDispatcher extends LazyDispatcher
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Biz
     */
    private $biz;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->container = $container;
    }

    public function dispatch($eventName, Event $event = null)
    {
        if (null === $event) {
            $event = new Event();
        }

        $event->setDispatcher($this);
        $event->setName($eventName);

        $subscribers = $this->container->get('codeags_plugin.event.lazy_subscribers');

        $callbacks = $subscribers->getCallbacks($eventName);

        foreach ($callbacks as $callback) {
            if ($event->isPropagationStopped()) {
                break;
            }

            list($id, $method) = $callback;
            if ($this->container->has($id)) {
                $subscriber = $this->container->get($id);
                $className = get_class($subscriber);
                $newSubscriber = new $className($this->biz);
                call_user_func(array($newSubscriber, $method), $event);
            }
        }

        return $event;
    }

    public function setBiz($biz)
    {
        $this->biz = $biz;
    }
}
