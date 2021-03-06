<?php

namespace Biz\Role\Service\Impl;

use AppBundle\Common\PluginVersionToolkit;
use Biz\BaseService;
use Biz\CloudPlatform\Service\AppService;
use Biz\Common\CommonException;
use Biz\Role\Dao\RoleDao;
use Biz\Role\RoleException;
use Biz\Role\Service\RoleService;
use Biz\Role\Util\PermissionBuilder;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\Tree;
use Biz\System\Service\LogService;
use Biz\System\Service\SettingService;
use Biz\User\Service\UserService;
use Symfony\Component\Yaml\Yaml;
use Topxia\Service\Common\ServiceKernel;

class RoleServiceImpl extends BaseService implements RoleService
{
    public function getRole($id)
    {
        return $this->getRoleDao()->get($id);
    }

    public function getRoleByCode($code)
    {
        return $this->getRoleDao()->getByCode($code);
    }

    public function createRole($role)
    {
        $role['createdTime'] = time();
        $user = $this->getCurrentUser();
        $role['createdUserId'] = $user['id'];
        $role = ArrayToolkit::parts($role, array('name', 'code', 'data', 'data_v2', 'createdTime', 'createdUserId'));

        if (!ArrayToolkit::requireds($role, array('name', 'code'))) {
            $this->createNewException(CommonException::ERROR_PARAMETER_MISSING());
        }

        if (!preg_match('/(^(?![^0-9a-zA-Z]+$))(?![0-9]+$).+/', $role['code'])) {
            $this->createNewException(RoleException::CODE_NOT_ALLL_DIGITAL());
        }

        return $this->getRoleDao()->create($role);
    }

    public function updateRole($id, array $fields)
    {
        $this->checkChangeRole($id);
        $fields = ArrayToolkit::parts($fields, array('name', 'code', 'data', 'data_v2'));

        if (isset($fields['code'])) {
            unset($fields['code']);
        }

        $fields['updatedTime'] = time();
        $role = $this->getRoleDao()->update($id, $fields);

        return $role;
    }

    public function deleteRole($id)
    {
        $role = $this->checkChangeRole($id);
        if (!empty($role)) {
            $this->getRoleDao()->delete($id);
        }
    }

    public function searchRoles($conditions, $sort, $start, $limit)
    {
        $conditions = $this->prepareSearchConditions($conditions);

        switch ($sort) {
            case 'created':
                $sort = array('createdTime' => 'DESC');
                break;
            case 'createdByAsc':
                $sort = array('createdTime' => 'ASC');
                break;

            default:
                $sort = array('createdTime' => 'DESC');
                break;
        }

        return $this->getRoleDao()->search($conditions, $sort, $start, $limit);
    }

    public function searchRolesCount($conditions)
    {
        $conditions = $this->prepareSearchConditions($conditions);

        return $this->getRoleDao()->count($conditions);
    }

    public function findRolesByCodes(array $codes)
    {
        if (empty($codes)) {
            return array();
        }

        return $this->getRoleDao()->findByCodes($codes);
    }

    public function isRoleNameAvalieable($name, $exclude = null)
    {
        if (empty($name)) {
            return false;
        }

        if ($name == $exclude) {
            return true;
        }

        $role = $this->getRoleDao()->getByName($name);

        return $role ? false : true;
    }

    public function isRoleCodeAvalieable($code, $exclude = null)
    {
        if ($code == $exclude) {
            return true;
        }

        $tag = $this->getRoleByCode($code);

        return $tag ? false : true;
    }

    /**
     * @param $tree  '??????menus???????????????'
     * @param $type  '?????????????????? admin|adminV2'
     *
     * @return array
     *               ???????????????????????????key???????????????
     */
    public function rolesTreeTrans($tree, $type = 'admin')
    {
        $biz = ServiceKernel::instance()->getBiz();
        $adminPermissionYml = $biz['role.get_permissions_yml'][$type];

        foreach ($tree as &$child) {
            $child['name'] = $this->trans($child['name'], array(), 'menu');
            //????????????????????????????????????????????????permissions???????????????????????????????????????????????????????????????????????????
            $child['permissions'] = empty($adminPermissionYml[$child['code']]) ? array() : $adminPermissionYml[$child['code']];
            if (isset($child['children'])) {
                $child['children'] = $this->rolesTreeTrans($child['children'], $type);
            }
        }

        return $tree;
    }

    /**
     * @param $permissions  '????????????????????????????????????Code'
     *
     * @return array
     *'??????????????????????????????????????????'
     */
    public function getAllParentPermissions($permissions)
    {
        $tree = PermissionBuilder::instance()->getOriginPermissionTree();
        $res = $tree->toArray();
        $nodes = $this->splitRolesTreeNode($res['children']);

        foreach ($permissions as $permission) {
            $permissions = $this->getParentRoleCodeArray($permission, $nodes, $permissions);
        }
        $permissions = array_unique($permissions);
        $permissions = array_filter($permissions);

        return array_values($permissions);
    }

    /**
     * @param $tree '??????menus???????????????'
     *
     * @return array
     *               ??????????????????????????????????????????????????????
     */
    public function filterRoleTree($tree)
    {
        $backstageSetting = $this->getSettingService()->get('backstage', array('is_v2' => 0));
        $isV2 = empty($backstageSetting['is_v2']) ? 'admin' : 'admin_v2';
        foreach ($tree as $key => $child) {
            if ($isV2 != $child['code']) {
                unset($tree[$key]);
            }
        }

        return array_values($tree);
    }

    /**
     * @param string $type
     *
     * @return array
     */
    public function getPermissionsYmlContent()
    {
        $permissions = array();

        $permissions['admin'] = $this->loadPermissionsFromAllConfig();

        $permissions['adminV2'] = $this->loadPermissionsFromAllConfig('adminV2');

        return $permissions;
    }

    /**
     * @param $tree '??????menus???????????????'
     * @param array $permissions '?????????????????????Array'
     *
     * @return array
     *
     * ?????????????????????????????????array(array('code'=>xxx,'parent'=>xxx))????????????
     */
    public function splitRolesTreeNode($tree, &$permissions = array())
    {
        foreach ($tree as &$child) {
            $permissions[$child['code']] = array(
                'code' => $child['code'],
                'parent' => isset($child['parent']) ? $child['parent'] : null,
            );
            if (isset($child['children'])) {
                $child['children'] = $this->splitRolesTreeNode($child['children'], $permissions);
            }
        }

        return $permissions;
    }

    /**
     * @param $code  '????????????code'
     * @param $permissions '?????????????????????Array, splitRolesTreeNode'
     * @param array $parentCodes
     *
     * @return array ???????????????????????????????????????code
     */
    public function getParentRoleCodeArray($code, $nodes, &$parentCodes = array())
    {
        if (!empty($nodes[$code]) && !empty($nodes[$code]['parent'])) {
            $parentCodes[] = $nodes[$code]['parent'];
            $parentCodes = $this->getParentRoleCodeArray($nodes[$code]['parent'], $nodes, $parentCodes);
        }

        return $parentCodes;
    }

    public function refreshRoles()
    {
        $permissions = PermissionBuilder::instance()->loadPermissionsFromAllConfig();
        $tree = Tree::buildWithArray($permissions, null, 'code', 'parent');
        //?????????????????????menus
        //?????????????????????menus
        $v2Roles = $this->getAdminV2Roles($tree);
        foreach ($v2Roles as $key => $value) {
            $userRole = $this->getRoleDao()->getByCode($key);

            if (empty($userRole)) {
                $this->initCreateRole($key, array_values($value), array_values($v2Roles[$key]));
            } else {
                $this->getRoleDao()->update($userRole['id'], array('data' => array_values($value), 'data_v2' => array_values($v2Roles[$key])));
            }
        }
    }

    /**
     * @param $tree
     *
     * @return array
     *
     * ?????????????????????menus
     */
    protected function getAdminV2Roles($tree)
    {
        $getAdminV2Roles = $tree->find(function ($tree) {
            return 'admin_v2' === $tree->data['code'];
        });

        $adminV2Roles = $getAdminV2Roles->column('code');

        $adminV2ForbidParentRoles = array(
            'admin_v2_user_avatar',
            'admin_v2_user_change_password',
            'admin_v2_my_cloud',
            'admin_v2_cloud_video',
            'admin_v2_cloud_sms',
            'admin_v2_cloud_search',
            'admin_v2_cloud_attachment_setting',
            'admin_v2_setting_cloud',
            'admin_v2_system',
        );

        $adminV2ForbidRoles = $this->getAllForbidRoles($getAdminV2Roles, $adminV2ForbidParentRoles);
        $superAdminV2Roles = array_merge($adminV2Roles, []);

        return array(
            'ROLE_USER' => array(),
            'ROLE_TEACHER' => [],
            'ROLE_ADMIN' => array_diff($superAdminV2Roles, $adminV2ForbidRoles),
            'ROLE_SUPER_ADMIN' => $superAdminV2Roles,
        );
    }

    /**
     * @param $tree
     * @param $forbidRoles
     *
     * @return array
     *
     * '??????admin|admin_v2???????????????????????????????????????????????????????????????????????????????????????code'
     */
    protected function getAllForbidRoles($tree, $forbidRoles)
    {
        $adminForbidRoles = array();
        foreach ($forbidRoles as $forbidRole) {
            $adminRole = $tree->find(function ($tree) use ($forbidRole) {
                return $tree->data['code'] === $forbidRole;
            });

            if (is_null($adminRole)) {
                continue;
            }

            $adminForbidRoles = array_merge($adminRole->column('code'), $adminForbidRoles);
        }

        return $adminForbidRoles;
    }

    private function initCreateRole($code, $role, $v2Role)
    {
        $userRoles = array(
            'ROLE_SUPER_ADMIN' => array('name' => '???????????????', 'code' => 'ROLE_SUPER_ADMIN'),
            'ROLE_ADMIN' => array('name' => '?????????', 'code' => 'ROLE_ADMIN'),
            'ROLE_TEACHER' => array('name' => '??????', 'code' => 'ROLE_TEACHER'),
            'ROLE_USER' => array('name' => '??????', 'code' => 'ROLE_USER'),
        );
        $userRole = $userRoles[$code];

        $userRole['data'] = $role;
        $userRole['data_v2'] = $v2Role;
        $userRole['createdTime'] = time();
        $userRole['createdUserId'] = $this->getCurrentUser()->getId();
        $this->getLogService()->info('role', 'init_create_role', '?????????????????????"'.$userRole['name'].'"', $userRole);

        return $this->getRoleDao()->create($userRole);
    }

    private function checkChangeRole($id)
    {
        $role = $this->getRoleDao()->get($id);
        $notUpdateRoles = array('ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_TEACHER', 'ROLE_USER');
        if (in_array($role['code'], $notUpdateRoles)) {
            $this->createNewException(RoleException::FORBIDDEN_MODIFY());
        }

        return $role;
    }

    protected function prepareSearchConditions($conditions)
    {
        if (!empty($conditions['nextExcutedStartTime']) && !empty($conditions['nextExcutedEndTime'])) {
            $conditions['nextExcutedStartTime'] = strtotime($conditions['nextExcutedStartTime']);
            $conditions['nextExcutedEndTime'] = strtotime($conditions['nextExcutedEndTime']);
        } else {
            unset($conditions['nextExcutedStartTime']);
            unset($conditions['nextExcutedEndTime']);
        }

        if (empty($conditions['cycle'])) {
            unset($conditions['cycle']);
        }

        return $conditions;
    }

    /**
     * ????????????????????????????????????????????????8.5.0????????????????????????????????????data_v2???????????????????????????data???????????????v2???menus
     */
    public function upgradeRoleDataV2()
    {
        $roles = $this->searchRoles(array('excludeCodes' => array('ROLE_USER', 'ROLE_TEACHER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN')), array(), 0, PHP_INT_MAX);

        foreach ($roles as &$role) {
            $role['data_v2'] = $this->getAdminV2Permissions($role['data']);
            $this->updateRole($role['id'], $role);
        }
    }

    protected function loadPermissionsFromAllConfig($type = 'admin')
    {
        $configs = $this->getPermissionConfig($type);
        $permissions = array();
        foreach ($configs as $config) {
            if (!file_exists($config)) {
                continue;
            }
            $menus = Yaml::parse(file_get_contents($config));
            if (empty($menus)) {
                continue;
            }

            $permissions = array_merge($permissions, $menus);
        }

        return $permissions;
    }

    protected function getPermissionConfig($type = 'admin')
    {
        $configPaths = array();

        $rootDir = ServiceKernel::instance()->getParameter('kernel.root_dir');
        if ('admin' == $type) {
            $files = array(
                $rootDir.'/../permissions.yml',
            );
        } else {
            $files = array(
                $rootDir.'/../permissions_v2.yml',
            );
        }

        foreach ($files as $filepath) {
            if (is_file($filepath)) {
                $configPaths[] = $filepath;
            }
        }

        $count = $this->getAppService()->findAppCount();
        $apps = $this->getAppService()->findApps(0, $count);

        foreach ($apps as $app) {
            if ('plugin' != $app['type']) {
                continue;
            }

            if ('MAIN' !== $app['code'] && $app['protocol'] < 3) {
                continue;
            }

            if (!PluginVersionToolkit::dependencyVersion($app['code'], $app['version'])) {
                continue;
            }

            $code = ucfirst($app['code']);
            if ('admin' == $type) {
                $configPaths[] = "{$rootDir}/../plugins/{$code}Plugin/permissions.yml";
            } else {
                $configPaths[] = "{$rootDir}/../plugins/{$code}Plugin/permissions_v2.yml";
            }
        }

        return $configPaths;
    }

    /**
     * @param $roleData
     *
     * @return array
     *               ??????role???data???menus???????????????admin_v2???menus
     */
    protected function getAdminV2Permissions($roleData)
    {
        $biz = ServiceKernel::instance()->getBiz();
        $adminPermissions = $biz['role.get_permissions_yml']['admin'];
        $roles = array();
        foreach ($roleData as $menu) {
            if (!empty($adminPermissions[$menu]) && is_array($adminPermissions[$menu])) {
                $roles = array_merge($roles, $adminPermissions[$menu]);
            }
        }

        return  $this->getAllParentPermissions($roles);
    }

    /**
     * @return AppService
     */
    protected function getAppService()
    {
        return $this->createService('CloudPlatform:AppService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('System:SettingService');
    }

    /**
     * @return RoleDao
     */
    protected function getRoleDao()
    {
        return $this->createDao('Role:RoleDao');
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->createService('System:LogService');
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }
}
