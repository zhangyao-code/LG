<?php

namespace Biz\News\Dao\Impl;

use Biz\News\Dao\NewsDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class NewsDaoImpl extends GeneralDaoImpl implements NewsDao
{
    protected $table = 'home_news';

    public function declares()
    {
        return array(
            'timestamps' => array('createdTime', 'updatedTime'),
            'serializes' => array(),
            'orderbys' => array(
                'createdTime',
                'updatedTime',
            ),
            'conditions' => array(
                'id = :id',
                'title like :title',
                'home= :home',
            ),
        );
    }
}
