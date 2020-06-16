<?php
/**
 * hyperf_admin 登录状态检测中间件
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
use HyperfAdmin\Admin\Service\AuthService;

class AuthMiddleware extends CoreMiddleware
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
     * @var AuthService
     */
    protected $auth_service;

    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request, LoggerFactory $logger)
    {
        $this->log = $logger->get('auth');
        $this->auth_service = make(AuthService::class);
        parent::__construct($container, 'http');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 检验登录状态
        $user = $this->auth_service->check();
        $this->log->info('当前登录用户信息', $user);

        return $handler->handle($request);
    }
}
