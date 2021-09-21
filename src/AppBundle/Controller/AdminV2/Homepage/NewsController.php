<?php

namespace AppBundle\Controller\AdminV2\Homepage;

use AppBundle\Common\Exception\FileToolkitException;
use AppBundle\Common\FileToolkit;
use AppBundle\Common\Paginator;
use AppBundle\Controller\AdminV2\BaseController;
use Biz\Content\Service\FileService;
use Biz\Content\Service\NavigationService;
use Biz\News\Service\Impl\NewsServiceImpl;
use Biz\System\Service\SettingService;
use Codeages\Biz\Framework\Service\Exception\NotFoundException;
use Symfony\Component\HttpFoundation\Request;

class NewsController extends BaseController
{
    public function indexAction(Request $request)
    {
        $paginator = new Paginator(
            $this->get('request'),
            $this->getNewsService()->countNews(array()),
            20
        );

        $news = $this->getNewsService()->searchNews(
            array('title'=> $request->query->get('title', '')),
            array('createdTime' => 'DESC'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return $this->render('admin-v2/news/index.html.twig', array(
            'news' => $news,
            'paginator' => $paginator,
        ));

    }

    public function createAction(Request $request)
    {
        if($request->getMethod() == 'POST'){
           $data = $request->request->all();
           if(!empty($data['noneeditor-body'])){
               $data['content'] = $data['noneeditor-body'];
           }
            if(!empty($data['richeditor-body'])){
                $data['content'] = $data['richeditor-body'];
            }
            $data['home'] = 1;
            $this->getNewsService()->createNews($data);
            return $this->createJsonResponse(true);
        }
        return $this->render('admin-v2/news/create.html.twig', array(
        ));
    }

    public function updateAction(Request $request, $id)
    {
        $new = $this->getNewsService()->getNews($id);
        if($request->getMethod() == 'POST'){
            $data = $request->request->all();
            if(!empty($data['noneeditor-body'])){
                $data['content'] = $data['noneeditor-body'];
            }
            if(!empty($data['richeditor-body'])){
                $data['content'] = $data['richeditor-body'];
            }
            $data['home'] = 1;
            $this->getNewsService()->updateNews($id, $data);
            return $this->createJsonResponse(true);
        }
        return $this->render('admin-v2/news/create.html.twig', array(
            'new'=>$new
        ));
    }

    public function deleteAction(Request $request, $id)
    {
        $this->getNewsService()->deleteNews($id);
        return $this->createJsonResponse(true);
    }

    public function showAction(Request $request, $id)
    {
        $this->getNewsService()->updateNews($id, array('home'=>$request->request->get('home', 1)));

        return $this->createJsonResponse(true);
    }

    /**
     * @return NewsServiceImpl
     */
    protected function getNewsService()
    {
        return $this->createService('News:NewsService');
    }

}
