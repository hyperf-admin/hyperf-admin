<?php
declare (strict_types=1);
namespace HyperfAdmin\Admin\Model;

use HyperfAdmin\BaseUtils\Model\BaseModel;

class OperatorLog extends BaseModel
{
    protected $table = 'operator_log';

    protected $connection = 'hyperf_admin';

    protected $fillable = [
        'page_url',
        'page_name',
        'action',
        'operator_id',
        'nickname',
        'relation_ids',//多个id-当前版本ID[id-current_version_id,]
        'detail_json',//需要灵活记录的json
        'client_ip', // 客户端地址
    ];
}
