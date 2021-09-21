<?php

namespace AppBundle\Controller\AdminV2\Homepage;

use AppBundle\Common\Exception\FileToolkitException;
use AppBundle\Common\FileToolkit;
use AppBundle\Controller\AdminV2\BaseController;
use Biz\Content\Service\FileService;
use Biz\Content\Service\NavigationService;
use Biz\System\Service\SettingService;
use Codeages\Biz\Framework\Service\Exception\NotFoundException;
use Symfony\Component\HttpFoundation\Request;

class HomepageController extends BaseController
{
    public function tabAction(Request $request)
    {
        $type = $request->query->get('type', 'homepage');

        $navigations = $this->getNavigationService()->getNavigationsListByType($type);

        return $this->render('admin-v2/homepage/tab.html.twig', array(
            'type' => 'homepage',
            'navigations' => $navigations,
        ));
    }

    public function footTabAction(Request $request)
    {
        $type = $request->query->get('type', 'foot');

        $navigations = $this->getNavigationService()->getNavigationsListByType($type);

        return $this->render('admin-v2/homepage/foot-tab.html.twig', array(
            'type' => 'foot',
            'navigations' => $navigations,
        ));
    }

    public function friendTabAction(Request $request)
    {
        $type = $request->query->get('type', 'friend');

        $navigations = $this->getNavigationService()->getNavigationsListByType($type);

        return $this->render('admin-v2/homepage/friend-tab.html.twig', array(
            'type' => 'friend',
            'navigations' => $navigations,
        ));
    }

    public function swiperSettingAction(Request $request)
    {
        if($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $this->getSettingService()->set('homepage_swiper', $data);
            return $this->createJsonResponse(true);
        }
        return $this->render('admin-v2/homepage/swiper.html.twig', array(
            'setting'=> $this->getSettingService()->get('homepage_swiper', array()),
        ));
    }

    public function uploadAction(Request $request)
    {
        $fileId = $request->request->get('id');
        $objectFile = $this->getFileService()->getFileObject($fileId);

        if (!FileToolkit::isImageFile($objectFile)) {
            $this->createNewException(FileToolkitException::NOT_IMAGE());
        }

        $file = $this->getFileService()->getFile($fileId);
        $parsed = $this->getFileService()->parseFileUri($file['uri']);

        $site['logo'] = "{$this->container->getParameter('topxia.upload.public_url_path')}/".$parsed['path'];
        $site['logo'] = ltrim($site['logo'], '/');


        $response = array(
            'path' => $site['logo'],
            'url' => $this->container->get('templating.helper.assets')->getUrl($site['logo']),
        );

        return $this->createJsonResponse($response);
    }

    /**
     * @return FileService
     */
    protected function getFileService()
    {
        return $this->createService('Content:FileService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    /**
     * @return NavigationService
     */
    protected function getNavigationService()
    {
        return $this->createService('Content:NavigationService');
    }

}
