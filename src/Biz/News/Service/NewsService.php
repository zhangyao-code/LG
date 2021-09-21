<?php

namespace Biz\News\Service;

interface NewsService
{
    public function getNews($id);

    public function countNews($conditions);

    public function searchNews($conditions, $orderBys, $start, $limit, $columns = array());

}
