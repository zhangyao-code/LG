<?php

namespace Biz\MessageBoard\Dao\Impl;

use Biz\News\Dao\NewsDao;
use Codeages\Biz\Framework\Dao\GeneralDaoImpl;

class BoardDaoImpl extends GeneralDaoImpl implements NewsDao
{
    protected $table = 'message_board';

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
                'mobile like :mobile',
                'email like :email',
                'company like :company',
            ),
        );
    }
}
