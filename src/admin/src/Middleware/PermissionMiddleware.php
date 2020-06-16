<?php
/**
 * hyperf_admin 鉴权中间件
 * 统一负责 资源权限校验
 */
namespace HyperfAdmin\Admin\Middleware;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hyperf\HttpServer\CoreMiddleware;
use Hyperf\Logger\LoggerFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use HyperfAdmin\Admin\Service\PermissionService;
use HyperfAdmin\Admin\Service\AuthService;
use HyperfAdmin\BaseUtils\AKSK;
use HyperfAdmin\BaseUtils\Constants\ErrorCode;

class PermissionMiddleware extends CoreMiddleware
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var HttpResponse
     */
    protected $response;

    /**
     * @var LoggerFactory
     */
    protected $log;

    /**
     * @var PermissionService
     */
    protected $permission_service;

    /**
     * @var AuthService
     */
    protected $auth_service;

    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request, LoggerFactory $logger)
    {
        $this->container = $container;
        $this->response = $response;
        $this->request = $request;
        $this->log = $logger->get('permission');
        $this->permission_service = make(PermissionService::class);
        $this->auth_service = make(AuthService::class);
        parent::__construct($container, 'http');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        $method = $request->getMethod();
        // 其他系统调用，走AKSK中间件验证
        $client_token = $request->getHeader('Authorization')[0] ?? '';
        if ($client_token) {
            if (!$this->akSkAuth($uri, $method, $client_token)) {
                return $this->fail(ErrorCode::CODE_ERR_DENY, '接口权限校验失败');
            }

            return $handler->handle($request);
        }
        // 内部请求时不做鉴权
        if ($this->getRealIp() == '127.0.0.1') {
            return $handler->handle($request);
        }
        // 开放资源，不进行鉴权
        if ($this->permission_service->isOpen($path, $method)) {
            return $handler->handle($request);
        }
        // 检验登录状态
        $user = $this->auth_service->user();
        if (empty($user)) {
            return $this->fail(ErrorCode::CODE_LOGIN, '请先登录');
        }

        if (!$this->permission_service->hasPermission($path, $method)) {
            return $this->fail(ErrorCode::CODE_NO_AUTH, "{$path}权限不足");
        }

        return $handler->handle($request);
    }

    protected function getRealIp()
    {
        return $this->request->header('x-real-ip');
    }

    /**
     * @param int         $code
     * @param string|null $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function fail(int $code = -1, ?string $message = null)
    {
        $response = [
            'code' => $code,
            'message' => $message ?: ErrorCode::getMessage($code),
            'payload' => (object)[],
        ];
        $this->log->warning($this->request->getUri()->getPath() . ' fail', $response);

        return $this->response->json($response);
    }

    private function akSkAuth($uri, $method, $client_token)
    {
        $host = env('APP_DOMAIN', '');
        $query = $this->request->getQueryParams();
        if (!empty($query)) {
            ksort($query);
            $query = http_build_query($query);
        } else {
            $query = '';
        }
        $path = $uri->getPath();
        $content_type = $this->request->getHeader('Content-type')[0] ?? '';
        $body = $this->request->getParsedBody();
        if (!empty($body)) {
            ksort($body);
            $body = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $body = '';
        }
        $ak = str_replace('ha ', '', explode(':', $client_token)[0] ?? '');
        $sk = config('client_user')[$ak] ?? '';
        $auth = new AKSK($ak, $sk);
        $token = $auth->token($method, $path, $host, $query, $content_type, $body);
        $this->log->info('aksk auth:', [
            'client_token' => $client_token,
            'except_token' => $token,
        ]);

        return $token === $client_token;
    }
}
