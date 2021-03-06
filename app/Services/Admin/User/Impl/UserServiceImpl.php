<?php

namespace App\Services\Admin\User\Impl;

use App\Events\UserRoleChanged;
use App\Exceptions\ParamterErrorException;
use App\Models\Admin;
use App\Models\BaseModel;
use App\Models\Role;
use App\Repository\Contracts\AdminRepository;
use App\Repository\Criteria\IsDeletedCriteria;
use App\Repository\Criteria\StateCriteria;
use App\Repository\Validators\AdminValidator;
use App\Services\Admin\User\UserService;
use App\Services\Helper\BatchChangeState;
use App\Services\Rbac\Role\RoleService;
use Hash;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Validator;

/**
 * Class UserServiceImpl
 *
 * 用户服务实现
 *
 * @author  SuperHappysir
 * @version 1.0
 * @package App\Services\Admin\User\Impl
 */
class UserServiceImpl implements UserService
{
    use BatchChangeState;
    
    /**
     *
     *
     * @var \App\Services\Rbac\Role\RoleService
     */
    private $roleService;
    
    /**
     * PermisssionServiceImpl constructor.
     *
     * @param \App\Repository\Contracts\AdminRepository $repository
     * @param \App\Services\Rbac\Role\RoleService       $roleService
     */
    public function __construct(AdminRepository $repository, RoleService $roleService)
    {
        $this->repostitory = $repository;
        $this->roleService = $roleService;
    }
    
    /**
     * 获取单个角色信息
     *
     * @param int   $id
     * @param array $columns
     *
     * @return BaseModel|null
     */
    public function find(int $id, $columns = [ '*' ])
    {
        return $this->repostitory->find($id, $columns);
    }
    
    /**
     * 创建一个模型
     *
     * @param array $attributes
     *
     * @return BaseModel
     */
    public function create(array $attributes)
    {
        $attributes = $this->ensurePasswordIsValid($attributes);
        
        return $this->repostitory->create($attributes);
    }
    
    /**
     * 获取分页列表
     *
     * @param int   $pageSize
     * @param array $columns
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate(int $pageSize, $columns = [ '*' ]) : LengthAwarePaginator
    {
        $this->repostitory->pushCriteria(app(IsDeletedCriteria::class));
        $this->repostitory->pushCriteria(app(StateCriteria::class));
        
        return $this->repostitory->paginate($pageSize, $columns);
    }
    
    /**
     * 更新一个模型
     *
     * @param array $attributes
     * @param int   $id
     *
     * @return BaseModel
     */
    public function update(array $attributes, int $id)
    {
        $attributes = $this->ensurePasswordIsValid($attributes);
    
        return $this->repostitory->update($attributes, $id);
    }
    
    /**
     * 保证password符合指定规则
     *
     * @param array $attributes
     *
     * @return array
     */
    private function ensurePasswordIsValid(array $attributes) : array
    {
        // 涉及密码的修改,因此将密码的Validator单独校验
        // I5-Repository对当前场景的支持有缺陷
        if (!empty($attributes['password'])) {
            $adminValidator = app(AdminValidator::class);
            $validator      = Validator::make(
                $attributes,
                $adminValidator->getRules(AdminValidator::RULE_PASSWORD),
                $adminValidator->getMessages()
            );
            if ($validator->fails()
                && $errorMsg = $validator->errors()->first('password')) {
                throw new ParamterErrorException($errorMsg);
            }
    
            $attributes['password'] = Hash::make($attributes['password']);
        }
        
        return $attributes;
}
    
    /**
     * 分配角色
     *
     * @param int   $userId    用户ID
     * @param array $roleIdArr 角色id数组
     *
     * @return bool
     */
    public function allotRole(int $userId, array $roleIdArr) : bool
    {
        if (!$userId) {
            throw new ParamterErrorException('请指定角色ID');
        }
    
        $model = Admin::findOrFail($userId);
        
        // 过滤参数中给的权限集合,重新获取系统中正常的的角色
        $permissionCollection = $this->roleService->getRoleCollectionByIdArr(
            $roleIdArr,
            ['is_deleted', 'id', 'state']
        );
        $permissionCollection = $permissionCollection->filter(function (Role $role) {
            return $role->isNormality();
        })->pluck('id');
    
        $status = $this->repostitory->allotRole($userId, $permissionCollection->toArray());
    
        event(new UserRoleChanged($model));
    
        return $status;
    }
    
    /**
     * 删除分配的角色
     *
     * @param int $userId 用户ID
     *
     * @return bool
     */
    public function deleteByUserId(int $userId) : bool
    {
        if (empty($userId)) {
            return false;
        }
    
        return $this->repostitory->clearRoleByUserId($userId);
    }
    
    /**
     * 获取用户角色
     *
     * @param int $userId
     *
     * @return Collection
     */
    public function getRoleByUserId(int $userId) : Collection
    {
        return $this->repostitory->getRoleCollectionByRoleId($userId);
    }
    
    /**
     * 获取用户角色
     *
     * @param int $userId
     *
     * @return Collection
     */
    public function getPermissionByUserId(int $userId) : Collection
    {
        return $this->roleService->getPermissionCollectionByRoleIdArr(
            $this->getRoleByUserId($userId)->pluck('id')->toArray()
        );
    }
}
