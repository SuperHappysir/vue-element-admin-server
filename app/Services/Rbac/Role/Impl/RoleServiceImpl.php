<?php

namespace App\Services\Rbac\Role\Impl;

use App\Constant\DeletedStateEnum;
use App\Models\Role;
use App\Repository\Contracts\RoleRepository;
use App\Repository\Criteria\IsDeletedCriteria;
use App\Repository\Criteria\Role\RoleNameLikeCriteria;
use App\Repository\Criteria\StateCriteria;
use App\Services\Rbac\Role\RoleService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class RoleServiceImpl
 *
 * Role角色实现类
 *
 * @author  luotao
 * @version 1.0
 * @package App\Services\Rbac\Role\Impl
 */
class RoleServiceImpl implements RoleService
{
    /**
     * role Repository
     *
     * @var RoleRepository
     */
    private $roleRepository;
    
    /**
     * RoleServiceImpl constructor.
     *
     * @param $roleRepository
     */
    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }
    
    /**
     * 获取分页列表
     *
     * @param int   $pageSize
     * @param array $columns
     *
     * @return LengthAwarePaginator
     */
    public function paginate(int $pageSize, $columns = [ '*' ]) : LengthAwarePaginator
    {
        $this->roleRepository->pushCriteria(app(IsDeletedCriteria::class));
        $this->roleRepository->pushCriteria(app(StateCriteria::class));
        $this->roleRepository->pushCriteria(app(RoleNameLikeCriteria::class));
        
        return $this->roleRepository->paginate($pageSize, $columns);
    }
    
    /**
     * 获取单个角色信息
     *
     * @param int   $roleId
     *
     * @param array $columns
     *
     * @return \App\Models\Role|null
     */
    public function find(int $roleId, $columns = [ '*' ]) : ?Role
    {
        return $this->roleRepository->find($roleId, $columns);
    }
    
    /**
     * 创建一个角色
     *
     * @param array $roleAttributes
     *
     * @return \App\Models\Role
     */
    public function create(array $roleAttributes) : Role
    {
        return $this->roleRepository->create($roleAttributes);
    }
    
    /**
     * 更新一个角色
     *
     * @param array $roleAttributes
     * @param int   $id
     *
     * @return Role
     */
    public function update(array $roleAttributes, int $id) : Role
    {
        return $this->roleRepository->update($roleAttributes, $id);
    }
    
    /**
     * 批量更新
     *
     * @param array $attributes
     * @param array $where
     *
     * @return int
     */
    public function batchUpdate(array $attributes, array $where) : int
    {
        if (empty($where) || empty($where['id_arr']) || !\is_array($where['id_arr'])) {
            throw new \RuntimeException('where参数传递错误');
        }
        
        return $this->roleRepository->updateWhere($attributes, function (Builder $builder) use ($where) {
            $builder->whereIn('id', $where['id_arr']);
        });
    }
    
    /**
     * 删除一个模型
     *
     * @param int $id
     *
     * @return mixed
     */
    public function delete(int $id) : bool
    {
        return (bool) $this->roleRepository->update([ 'is_deleted' => DeletedStateEnum::IS_DELETED ], $id);
    }
}