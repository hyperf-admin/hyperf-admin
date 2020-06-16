<?php
declare (strict_types=1);
namespace HyperfAdmin\Admin\Model;

use Hyperf\Database\Model\Events\Saved;
use Hyperf\Database\Model\Events\Saving;
use Hyperf\Utils\Context;
use Psr\Http\Message\ServerRequestInterface;
use HyperfAdmin\BaseUtils\JWT;

trait Versionable
{
    protected $versioning_enable = true;

    protected $is_update;

    public function isVersionEnable()
    {
        return $this->versioning_enable;
    }

    /**
     * 启动版本控制
     *
     * @return $this
     */
    public function enableVersioning()
    {
        $this->versioning_enable = true;
        return $this;
    }

    /**
     * 关闭版本控制
     *
     * @return $this
     */
    public function disableVersioning()
    {
        $this->versioning_enable = false;
        return $this;
    }

    /**
     * 定义版本关联
     *
     * @return mixed
     */
    public function versions()
    {
        return $this->morphMany(Version::class, null, 'table', 'pk');
    }

    public function getMorphClass()
    {
        if (strpos($this->getTable(), '.') !== false) {
            return $this->getTable();
        }
        return $this->getConnectionName() . '.' . $this->getTable();
    }

    /**
     * 是否是一次有效的版本控制
     *
     * @return bool
     */
    private function isValid()
    {
        $versionable_fields = $this->getVersionableFields();
        return $this->versioning_enable
               && Context::has(ServerRequestInterface::class)
               && $this->isDirty($versionable_fields);
    }

    public function getVersionableFields()
    {
        $remove_version_keys = $this->versioning_expect_fields ?? [];
        $remove_version_keys[] = $this->getUpdatedAtColumn();
        if (method_exists($this, 'getDeletedAtColumn')) {
            $remove_version_keys[] = $this->getDeletedAtColumn();
        }
        return collect($this->getAttributes())->except($remove_version_keys)->keys()->all();
    }

    public function saving(Saving $event)
    {
        $this->is_update = $this->exists;
    }

    /**
     * 监听saved事件保存表更数据
     *
     * @param Saved $event
     */
    public function saved(Saved $event)
    {
        if (!$this->isValid()) {
            return;
        }

        $request_log = $this->processRequest();

        $version = new Version();
        $version->pk = $this->getKey();
        $version->table = strpos($this->getTable(), '.') ? $this->getTable() : $this->getConnectionName() . '.' . $this->getTable();
        $version->content = $this->getAttributes();
        $versionable_fields = $this->getVersionableFields();
        $changes = array_remove_keys_not_in($this->getChanges(), $versionable_fields);
        $version->modify_fields = array_keys($changes);
        $version->action = $this->is_update ? 'update' : 'insert';
        $version->user_id = $request_log->user_id;
        /** @var ServerRequestInterface $request */
        $request = container(ServerRequestInterface::class);
        $version->req_id = $request->getAttribute('_req_id');
        $version->save();

        $this->clearOldVersions();
    }

    /**
     * 记录数据版本前先记录请求
     *
     * @return RequestLog
     */
    private function processRequest(): RequestLog
    {
        /** @var ServerRequestInterface $request */
        $request = container(ServerRequestInterface::class);

        $req_id = $request->getAttribute('_req_id');
        if (empty($req_id)) {
            $req_id = id_gen();
            $request = Context::set(ServerRequestInterface::class, $request->withAttribute('_req_id', $req_id));
            return RequestLog::create([
                'host' => $request->getUri()->getHost(),
                'path' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
                'header' => $request->getHeaders(),
                'params' => $request->getParsedBody(),
                'user_id' => JWT::verifyToken(cookie('X-Token') ?? '')['user_info']['id'] ?? 0,
                'req_id' => $req_id,
            ]);
        } else {
            return RequestLog::where('req_id', $req_id)->first();
        }
    }

    /**
     * 默认保留100个数据版本，可被覆盖
     */
    private function clearOldVersions()
    {
        $keep = $this->keep_version_count ?? 100;
        $count = $this->versions()->count();
        if ($keep > 0 && $count > $keep) {
            $this->versions()->limit($count - $keep)->delete();
        }
    }

    /**
     * 返回当前版本的数据
     *
     * @return mixed
     */
    public function currentVersion()
    {
        return $this->versions()->orderBy(Version::CREATED_AT, 'DESC')->orderBy('id', 'DESC')->first();
    }

    /**
     * 前几个版本
     *
     * @param int $previous
     *
     * @return mixed
     */
    public function previousVersion(int $previous = 1)
    {
        return $this->versions()->orderBy(Version::CREATED_AT, 'DESC')->orderBy('id', 'DESC')->offset($previous)->first();
    }

    /**
     * 最后几个版本
     *
     * @param int $num
     *
     * @return mixed
     */
    public function lastVersion($num = 1)
    {
        return $this->versions()->orderBy('id', 'DESC')->limit($num)
            //->offset(1) // 排除当前版本
            ->get();
    }
}
