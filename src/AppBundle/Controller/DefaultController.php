<?php

namespace AppBundle\Controller;

use AppBundle\Common\Paginator;
use Biz\News\Service\Impl\NewsServiceImpl;
use Codeages\Biz\Framework\Service\Exception\NotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends BaseController
{
    public function indexAction(Request $request)
    {
        $news = $this->getNewsService()->searchNews(array('home'=>1),array('createdTime'=>'desc'), 0, 10);
        return $this->render('homepage/index.html.twig', array(
            'news'=> $news
        ));
    }


    public function messageBoardAction(Request $request)
    {
//        $data = $request->request->all();
//        $data['ip'] = $request->getClientIp();
//        $this->createService('MessageBoard:BoardService')->createBoard($data);
        return $this->render('board/index.html.twig', array(

        ));
    }

    public function messagePostAction(Request $request)
    {
        $data = $request->request->all();
        $data['ip'] = $request->getClientIp();
        $this->createService('MessageBoard:BoardService')->createBoard($data);
        return $this->createJsonResponse(true);
    }

    public function newsAction(Request $request, $id)
    {
        $new = $this->getNewsService()->getNews($id);
        $new = $this->getNewsService()->updateNews($id, array('watch'=>$new['watch']+1));
        return $this->render('homepage/new-show.html.twig', array(
            'new'=> $new
        ));
    }

    public function newsListAction(Request $request)
    {
        $paginator = new Paginator(
            $request,
            $this->getNewsService()->countNews(array('title'=>$request->query->get('title', ''))),
            10
        );
        $news = $this->getNewsService()->searchNews(
            array('title'=>$request->query->get('title', '')),
            array('createdTime'=>'desc'),
            $paginator->getOffsetCount(),
            $paginator->getPerPageCount()
        );

        return $this->render('homepage/news-list.html.twig', array(
            'news' => $news,
            'paginator' => $paginator,
        ));
    }

    public function appDownloadAction()
    {
        $meCount = $this->getMeCount();
        $mobileCode = (empty($meCount['mobileCode']) ? 'edusohov3' : $meCount['mobileCode']);

        if ($this->getWebExtension()->isMicroMessenger() && 'edusohov3' == $mobileCode) {
            $url = 'http://a.app.qq.com/o/simple.jsp?pkgname=com.edusoho.kuozhi';
        } else {
            $url = $this->generateUrl('mobile_download', array('from' => 'qrcode', 'code' => $mobileCode), true);
        }

        return $this->render('mobile/app-download.html.twig', array(
            'url' => $url,
        ));
    }

    public function promotedTeacherBlockAction()
    {
        $teacher = $this->getUserService()->findLatestPromotedTeacher(0, 1);

        if ($teacher) {
            $teacher = $teacher[0];
            $teacher = array_merge(
                $teacher,
                $this->getUserService()->getUserProfile($teacher['id'])
            );
        }

        if (isset($teacher['locked']) && '0' !== $teacher['locked']) {
            $teacher = null;
        }

        return $this->render('default/promoted-teacher-block.html.twig', array(
            'teacher' => $teacher,
        ));
    }

    public function latestReviewsBlockAction($number)
    {
        $reviews = $this->getReviewService()->searchReviews(array('private' => 0), 'latest', 0, $number);
        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($reviews, 'userId'));
        $courses = $this->getCourseService()->findCoursesByIds(ArrayToolkit::column($reviews, 'courseId'));

        $courseSets = $this->getCourseSetService()->findCourseSetsByIds(ArrayToolkit::column($courses, 'courseSetId'));
        $courseSets = ArrayToolkit::index($courseSets, 'id');

        return $this->render('default/latest-reviews-block.html.twig', array(
            'reviews' => $reviews,
            'users' => $users,
            'courses' => $courses,
            'courseSets' => $courseSets,
        ));
    }

    public function topNavigationAction($siteNav = null, $isMobile = false)
    {
        $navigations = $this->getNavigationService()->getOpenedNavigationsTreeByType('top');

        return $this->render('default/top-navigation.html.twig', array(
            'navigations' => $navigations,
            'siteNav' => $siteNav,
            'isMobile' => $isMobile,
        ));
    }

    public function footNavigationAction()
    {
        $navigations = $this->getNavigationService()->findNavigationsByType('foot', 0, 100);

        return $this->render('default/foot-navigation.html.twig', array(
            'navigations' => $navigations,
        ));
    }

    public function friendlyLinkAction()
    {
        $friendlyLinks = $this->getNavigationService()->getOpenedNavigationsTreeByType('friendlyLink');

        return $this->render('default/friend-link.html.twig', array(
            'friendlyLinks' => $friendlyLinks,
        ));
    }

    public function customerServiceAction()
    {
        $customerServiceSetting = $this->getSettingService()->get('customerService', array());

        return $this->render('default/customer-service-online.html.twig', array(
            'customerServiceSetting' => $customerServiceSetting,
        ));
    }

    public function jumpAction(Request $request)
    {
        $courseId = (int) ($request->query->get('id'));

        if ($this->getCourseMemberService()->isCourseTeacher($courseId, $this->getCurrentUser()->id)) {
            $url = $this->generateUrl('live_course_manage_replay', array('id' => $courseId));
        } else {
            $url = $this->generateUrl('course_show', array('id' => $courseId));
        }

        $jumpScript = "<script type=\"text/javascript\"> if (top.location !== self.location) {top.location = \"{$url}\";}</script>";

        return new Response($jumpScript);
    }

    public function coursesCategoryAction(Request $request)
    {
        $conditions = $request->query->all();
        $conditions['status'] = 'published';
        $conditions['parentId'] = 0;
        $categoryId = isset($conditions['categoryId']) ? $conditions['categoryId'] : 0;
        $orderBy = $conditions['orderBy'];
        $courseType = isset($conditions['courseType']) ? $conditions['courseType'] : 'course';

        $config = $this->getThemeService()->getCurrentThemeConfig();

        if (!empty($config['confirmConfig'])) {
            $config = $config['confirmConfig']['blocks']['left'];

            foreach ($config as $template) {
                if (('course-grid-with-condition-index' == $template['code'] && 'course' == $courseType)
                    || ('open-course' == $template['code'] && 'open-course' == $courseType)) {
                    $config = $template;
                }
            }

            $config['orderBy'] = $orderBy;
            $config['categoryId'] = $categoryId;

            return $this->render('default/'.$config['code'].'.html.twig', array(
                'config' => $config,
            ));
        } else {
            return $this->render('default/course-grid-with-condition-index.html.twig', array(
                'categoryId' => $categoryId,
                'orderBy' => $orderBy,
            ));
        }
    }

    public function translateAction(Request $request)
    {
        $locale = $request->query->get('language');
        $targetPath = $request->query->get('_target_path');

        $request->getSession()->set('_locale', $locale);

        $currentUser = $this->getCurrentUser();

        if ($currentUser->isLogin()) {
            $this->getUserService()->updateUserLocale($currentUser['id'], $locale);
        }

        return $this->redirectSafely($targetPath);
    }

    public function clientTimeCheckAction(Request $request)
    {
        $clientTime = $request->request->get('clientTime');
        $clientTime = strtotime($clientTime);

        if ($clientTime < time()) {
            return $this->createJsonResponse(false);
        }

        return $this->createJsonResponse(true);
    }

    private function getMeCount()
    {
        $meCount = $this->setting('meCount', false);
        if (false === $meCount) {
            //???????????????????????????
            $result = CloudAPIFactory::create('leaf')->get('/me');
            $this->getSettingService()->set('meCount', $result);
        }
        $meCount = $this->setting('meCount');

        return $meCount;
    }

    private function isCustom()
    {
        $result = $this->getMeCount();

        return isset($result['hasMobile']) ? $result['hasMobile'] : 0;
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->getBiz()->service('System:SettingService');
    }

    /**
     * @return NavigationService
     */
    protected function getNavigationService()
    {
        return $this->getBiz()->service('Content:NavigationService');
    }

    /**
     * @return BlockService
     */
    protected function getBlockService()
    {
        return $this->getBiz()->service('Content:BlockService');
    }

    /**
     * @return CourseService
     */
    protected function getCourseService()
    {
        return $this->getBiz()->service('Course:CourseService');
    }

    protected function getReviewService()
    {
        return $this->getBiz()->service('Course:ReviewService');
    }

    /**
     * @return CategoryService
     */
    protected function getCategoryService()
    {
        return $this->getBiz()->service('Taxonomy:CategoryService');
    }

    /**
     * @return AppService
     */
    protected function getAppService()
    {
        return $this->getBiz()->service('CloudPlatform:AppService');
    }

    /**
     * @return ClassroomService
     */
    protected function getClassroomService()
    {
        return $this->getBiz()->service('Classroom:ClassroomService');
    }

    /**
     * @return ThemeService
     */
    protected function getThemeService()
    {
        return $this->getBiz()->service('Theme:ThemeService');
    }

    /**
     * @return NewsServiceImpl
     */
    protected function getNewsService()
    {
        return $this->createService('News:NewsService');
    }
}
