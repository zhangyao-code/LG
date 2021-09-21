<?php

namespace AppBundle\Handler;

use Biz\CloudPlatform\QueueJob\PushJob;
use Biz\User\CurrentUser;
use AppBundle\Common\ArrayToolkit;
use Biz\Course\Service\MemberService;
use Biz\System\Service\SettingService;
use Codeages\Biz\Framework\Context\Biz;
use Biz\CloudPlatform\Service\AppService;
use Biz\User\Service\NotificationService;
use Codeages\Biz\Framework\Queue\Service\QueueService;
use VipPlugin\Biz\Vip\Service\LevelService;
use VipPlugin\Biz\Vip\Service\VipService;
use Biz\Classroom\Service\ClassroomService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Biz\Course\Util\CourseTitleUtils;

class GenerateNotificationHandler
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
        $this->container = $container;
        $this->biz = $this->container->get('biz');
    }

    /**
     * Do the magic.
     *
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $this->biz['user'];

    }

    public function generateUrl(
        $route,
        array $parameters = array(),
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ) {
        return $this->container->get('router')->generate($route, $parameters, $referenceType);
    }

    private function createPushJob($from, $to, $body)
    {
        $pushJob = new PushJob(array(
            'from' => $from,
            'to' => $to,
            'body' => $body,
        ));

        $this->getQueueService()->pushJob($pushJob);
    }

    private function getConvNo()
    {
        $imSetting = $this->getSettingService()->get('app_im', array());
        $convNo = isset($imSetting['convNo']) && !empty($imSetting['convNo']) ? $imSetting['convNo'] : '';

        return $convNo;
    }

    /**
     * @return QueueService
     */
    protected function getQueueService()
    {
        return $this->biz->service('Queue:QueueService');
    }

    /**
     * @return ClassroomService
     */
    private function getClassroomService()
    {
        return $this->biz->service('Classroom:ClassroomService');
    }

    /**
     * @return NotificationService
     */
    private function getNotificationService()
    {
        return $this->biz->service('User:NotificationService');
    }

    /**
     * @return AppService
     */
    private function getAppService()
    {
        return $this->biz->service('CloudPlatform:AppService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->biz->service('System:SettingService');
    }

    /**
     * @return VipService
     */
    protected function getVipService()
    {
        return $this->biz->service('VipPlugin:Vip:VipService');
    }

    /**
     * @return LevelService
     */
    protected function getLevelService()
    {
        return $this->biz->service('VipPlugin:Vip:LevelService');
    }

    /**
     * @return MemberService
     */
    protected function getCourseMemberService()
    {
        return $this->biz->service('Course:MemberService');
    }

    protected function isPluginInstalled($pluginName)
    {
        return $this->container->get('kernel')->getPluginConfigurationManager()->isPluginInstalled($pluginName);
    }
}
