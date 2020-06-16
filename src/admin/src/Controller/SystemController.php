<?php
namespace HyperfAdmin\Admin\Controller;

use HyperfAdmin\Admin\Model\ExportTasks;
use HyperfAdmin\Admin\Service\CommonConfig;
use HyperfAdmin\Admin\Service\ExportService;
use HyperfAdmin\BaseUtils\Constants\ErrorCode;

class SystemController extends AdminAbstractController
{
    public function state()
    {
        $swoole_server = swoole_server();

        return $this->success([
            'state' => $swoole_server->stats(),
        ]);
    }

    public function config()
    {
        $config = CommonConfig::getValue('system', 'website_config', [
            'open_export' => false,
            'navbar_notice' => '',
        ]);

        return $this->success($config);
    }
}
