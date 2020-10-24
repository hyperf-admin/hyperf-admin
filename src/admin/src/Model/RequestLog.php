<?php
declare (strict_types=1);
namespace HyperfAdmin\Admin\Model;

use HyperfAdmin\BaseUtils\Model\BaseModel;

class RequestLog extends BaseModel
{
    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    protected $connection = 'hyperf_admin';

    protected $table = 'request_log';

    protected $fillable = [
        'host',
        'method',
        'path',
        'header',
        'params',
        'user_id',
        'req_id',
    ];

    protected $casts = [
        'header' => 'array',
        'params' => 'array',
        'user_id' => 'integer',
        'req_id' => 'integer',
    ];

    /**
     * 获取产生当前版本的用户信息
     *
     * @return \Hyperf\Database\Model\Model|\Hyperf\Database\Model\Relations\BelongsTo|object|null
     */
    public function getUser()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->first();
    }
}
