<?php

namespace Topxia\MobileBundleV2\Processor\Impl;

use Topxia\MobileBundleV2\Processor\BaseProcessor;
use Topxia\MobileBundleV2\Processor\CategoryProcessor;

class CategoryProcessorImpl extends BaseProcessor implements CategoryProcessor
{
    public function getTags()
    {
        $tags = $this->getTagService()->findAllTags(0, 100);
        $tags = array_map(function ($tag) {
            $tag['createdTime'] = date('c', $tag['createdTime']);

            return $tag;
        }, $tags);

        return $tags;
    }

    public function getCategories()
    {
        $category = $this->getParam('category');

        if (empty($category)) {
            $categories = $this->controller->getCategoryService()->findGroupRootCategories('course');
        } else {
            $group = $this->controller->getCategoryService()->getCategoryByCode($category);
            if (empty($group)) {
                $categories = array();
            } else {
                $ids = $this->controller->getCategoryService()->findCategoryChildrenIds($group['id']);
                $categories = $this->controller->getCategoryService()->findCategoriesByIds($ids);
                $categories = array_values($categories);
            }
        }

        return $categories;
    }

    private function coverCategoryChilds($categories)
    {
        $realityDepth = 0;
        $categorieStack = array();

        foreach ($categories as $key => $categorie) {
            if (empty($categorieStack)) {
                array_push($categorieStack, $categorie);
                continue;
            }

            $popCategory = &$categorieStack[count($categorieStack) - 1];
            $popDepth = $popCategory['depth'];
            $depth = $categorie['depth'];
            if ($depth > 0 && $depth > $popDepth) {
                if (!isset($popCategory['childs'])) {
                    $popCategory['childs'] = array();
                }

                array_push($categorieStack, $categorie);
                $count = count($categorieStack);
                if ($realityDepth < $count) {
                    ++$realityDepth;
                }
                $popCategory['childs'][] = &$categorieStack[$count - 1];
            } else {
                //?????????????????????
                $popChildCategory = end($categorieStack);
                $popChildDepth = $popChildCategory['depth'];
                while ($depth <= $popChildDepth) {
                    //??????????????????depth??????????????????????????????depth??????????????????
                    array_pop($categorieStack);
                    $popChildCategory = end($categorieStack);
                    $popChildDepth = $popChildCategory['depth'];
                }

                //????????????????????????????????????????????????????????????
                $popCategory = &$categorieStack[count($categorieStack) - 1];
                array_push($categorieStack, $categorie);
                $popCategory['childs'][] = &$categorieStack[count($categorieStack) - 1];
            }
        }

        if (count($categorieStack) > 1) {
            array_pop($categorieStack);
        }

        return array($categorieStack, $realityDepth);
    }

    public function getCategorieTree()
    {
        $type = $this->getParam('type', 'course');

        $group = $this->controller->getCategoryService()->getGroupByCode($type);
        if (empty($group)) {
            return array();
        }

        $categories = $this->controller->getCategoryService()->getCategoryTree($group['id']);

        array_unshift($categories, array(
            'id' => '0',
            'code' => 'root',
            'name' => '????????????',
            'icon' => '',
            'path' => '0',
            'weight' => '0',
            'groupId' => '0',
            'description' => '????????????',
            'depth' => '0',
            ));

        list($coverCategorys, $realityDepth) = $this->coverCategoryChilds($categories);

        return array(
            'realityDepth' => $realityDepth,
            'depth' => $group['depth'],
            'data' => $coverCategorys,
            );

        return $coverCategorys;
    }

    public function getAllCategories()
    {
        $group = $this->controller->getCategoryService()->getGroupByCode('course');
        if (empty($group)) {
            return array();
        }

        $categories = $this->controller->getCategoryService()->getCategoryTree($group['id']);

        array_unshift($categories, array(
            'id' => '0',
            'code' => 'root',
            'name' => '????????????',
            'icon' => '',
            'path' => '0',
            'weight' => '0',
            'groupId' => '0',
            'description' => '????????????',
            'depth' => '0',
            ));

        return $categories;
    }

    private function sortCategories($categories)
    {
        $newCategories = array();
        $categorieIds = array();
        foreach ($categories as $categorie) {
            $parentId = $categorie['parentId'];
            if (!array_key_exists($parentId, $categorieIds)) {
                $newCategories[] = $this->getCategory($parentId);
                $categorieIds[$parentId] = null;
            }
            $newCategories[] = $categorie;
        }

        return $newCategories;
    }

    private function getCategory($id)
    {
        if (0 == $id) {
            return array(
                'id' => '0',
                'code' => 'group',
                'name' => '??????',
                'icon' => '',
                'path' => '',
                'weight' => '0',
                'groupId' => '0',
                'parentId' => '0',
                'description' => null,
                );
        }

        $categorie = $this->controller->getCategoryService()->getCategory($id);
        $categorie['code'] = 'group';

        return $categorie;
    }
}
