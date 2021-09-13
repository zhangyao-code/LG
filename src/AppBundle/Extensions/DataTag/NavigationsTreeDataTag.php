<?php

namespace AppBundle\Extensions\DataTag;

class NavigationsTreeDataTag extends BaseDataTag implements DataTag
{
    /**
     * 获取导航列表.
     *
     * @param array $arguments 参数
     *
     * @return array 导航列表
     */
    public function getData(array $arguments)
    {
        $type = empty($arguments['type']) ? 'top' : $arguments['type'];

        return $this->getNavigationService()->getOpenedNavigationsTreeByType($type);
    }

    protected function getNavigationService()
    {
        return $this->getServiceKernel()->createService('Content:NavigationService');
    }
}
