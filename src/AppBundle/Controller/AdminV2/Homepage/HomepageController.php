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

    public function tabSettingAction(Request $request)
    {

        return $this->render('admin-v2/homepage/setting.html.twig', array(
        ));
    }

    public function swiperSettingAction(Request $request)
    {
        if($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $this->getSettingService()->set('homepage_swiper', $data['swiper']);
            return $this->createJsonResponse(true);
        }
        return $this->render('admin-v2/homepage/swiper.html.twig', array(
            'swiper'=> $this->getSettingService()->get('homepage_swiper', array()),
            'solute'=> $this->getSettingService()->get('homepage_solute', array()),
            'trace' => $this->getSettingService()->get('homepage_trace', array()),
            'detail'=> $this->getSettingService()->get('homepage_detail', array()),
        ));
    }

    public function soluteSettingAction(Request $request)
    {
        if($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $this->getSettingService()->set('homepage_solute', $data);
            return $this->createJsonResponse(true);
        }
    }

    public function traceSettingAction(Request $request)
    {
        if($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $this->getSettingService()->set('homepage_trace', $data);
            return $this->createJsonResponse(true);
        }
    }

    public function detailSettingAction(Request $request)
    {
        if($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $this->getSettingService()->set('homepage_detail', $data);
            return $this->createJsonResponse(true);
        }
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
