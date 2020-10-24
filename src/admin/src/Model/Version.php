<?php
declare (strict_types=1);
namespace HyperfAdmin\Admin\Model;

use HyperfAdmin\BaseUtils\Model\BaseModel;

class Version extends BaseModel
{
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $connection = 'hyperf_admin';

    protected $table = 'data_version';

    protected $fillable = [
        'pk',
        'table',
        'content',
        'req_id',
        'user_id',
        'action',
        'modify_fields'
    ];

    protected $casts = [
        'content' => 'array',
        'modify_fields' => 'array',
    ];

    public function versionable()
    {
        return $this->morphTo(null, 'table', 'pk');
    }

    /**
     * 获取产生当前版本的请求信息
     * @return \Hyperf\Database\Model\Model|\Hyperf\Database\Model\Relations\BelongsTo|object|null
     */
    public function getRequest()
    {
        return $this->belongsTo(RequestLog::class, 'req_id', 'req_id')->first();
    }

    /**
     * 获取产生当前版本的用户信息
     * @return \Hyperf\Database\Model\Model|\Hyperf\Database\Model\Relations\BelongsTo|object|null
     */
    public function getUser()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->first();
    }

    /**
     * TODO: 该方法应该不可用
     * 回滚版本
     * @return mixed
     */
    public function revert()
    {
        $model = new $this->versionable_type();
        $model->unguard();
        $model->fill($this->content);
        $model->exists = true;
        $model->reguard();

        unset($model->{$model->getCreatedAtColumn()});
        unset($model->{$model->getUpdatedAtColumn()});
        if (method_exists($model, 'getDeletedAtColumn')) {
            unset($model->{$model->getDeletedAtColumn()});
        }

        $model->save();
        return $model;
    }
}
