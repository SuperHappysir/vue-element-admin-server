<?php

namespace App\Repository\Contracts;

/**
 * Interface RolePermissionRepository.
 * @package namespace App\Repository\Contracts;
 */
interface RolePermissionRepository extends BaseRepostitory
{
    /**
     * 根据条件删除
     * @param array $where
     * @return mixed
     */
    public function deleteWhere(array $where);
    
    /**
     * 删除角色ID上的权限
     * @param int $roleId
     * @return int
     */
    public function deletePermissionByRoleId(int $roleId) : int;
    
    /**
     * 根据角色ID获取角色权限path路径
     *
     * @param int $roleId
     *
     * @return array
     */
    public function getPermissionIdArrByRoleId(int $roleId) : array;
}
