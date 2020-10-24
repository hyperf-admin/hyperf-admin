<?php

namespace HyperfAdmin\Admin\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;
use HyperfAdmin\Admin\Model\User;
use Hyperf\Di\Annotation\Inject;
use HyperfAdmin\Admin\Service\AuthService;
use HyperfAdmin\Admin\Service\PermissionService;
use HyperfAdmin\BaseUtils\Scaffold\Controller\AbstractController;

abstract class AdminAbstractController extends AbstractController
{
    /*
     * @Inject()
     * @var AuthService
     */
    protected $auth_service;

    /*
     * @Inject()
     * @var PermissionService
     */
    protected $permission_service;

    public function __construct(ContainerInterface $container, RequestInterface $request, ResponseInterface $response)
    {
        $this->auth_service = make(AuthService::class);
        $this->permission_service = make(PermissionService::class);

        parent::__construct($container, $request, $response);
    }

    public function getRecordHistory()
    {
        $history_versions = $this->getEntity()->lastVersion($this->previous_version_number);
        $history_versions = array_node_append($history_versions, 'user_id', 'username', function ($uids) {
            $ret = User::query()->select(['id', 'username'])->whereIn('id', $uids)->get();
            if (!$ret) {
                return [];
            }
            $ret = $ret->toArray();
            array_change_v2k($ret, 'id');
            foreach ($ret as &$item) {
                $item = $item['username'];
                unset($item);
            }
            return $ret;
        });
        return $history_versions;
    }

    /**
     * 新版本检查
     *
     * @param int $id
     * @param int $last_ver_id
     *
     * @return array
     */
    public function newVersion(int $id, int $last_ver_id)
    {
        $last = $this->getEntity()->lastVersion();
        if (!$last || $last->id == $last_ver_id) {
            return $this->success(['has_new_version' => false]);
        }
        if ($last->user_id == auth()->get('id')) {
            return $this->success(['has_new_version' => false]);
        }
        $user = User::query()->find($last->user_id);
        return $this->success([
            'has_new_version' => true,
            'message' => sprintf("%s在%s保存了新的数据, 请刷新页面获取最新数据", $user->username, $last->created_at),
        ]);
    }

    public function userId()
    {
        return auth()->get('id');
    }
}
