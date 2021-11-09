<?php

namespace Biz\MessageBoard\Service;

interface BoardService
{
    public function getBoard($id);

    public function countBoard($conditions);

    public function searchBoard($conditions, $orderBys, $start, $limit, $columns = array());

}
