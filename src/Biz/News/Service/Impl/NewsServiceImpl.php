<?php

namespace Biz\News\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use Biz\BaseService;
use Biz\News\Dao\NewsDao;
use Biz\News\Service\NewsService;

class NewsServiceImpl extends BaseService implements NewsService
{
    public function createNews($new)
    {
        return $this->getNewsDao()->create(ArrayToolkit::parts($new, array('title', 'icon', 'content', 'editor', 'home')));
    }

    public function updateNews($id, $new)
    {
        return $this->getNewsDao()->update($id, ArrayToolkit::parts($new, array('title', 'icon', 'content', 'editor', 'home', 'watch')));
    }

    public function deleteNews($id)
    {
        return $this->getNewsDao()->delete($id);
    }

    public function getNews($id)
    {
        return $this->getNewsDao()->get($id);
    }

    public function countNews($conditions)
    {
        return $this->getNewsDao()->count($conditions);
    }

    public function searchNews($conditions, $orderBys, $start, $limit, $columns = array())
    {
        return $this->getNewsDao()->search($conditions, $orderBys, $start, $limit, $columns);
    }

    /**
     * @return NewsDao
     */
    protected function getNewsDao()
    {
        return  $this->createDao('News:NewsDao');
    }
}
