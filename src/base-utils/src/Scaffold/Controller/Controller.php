<?php
namespace HyperfAdmin\BaseUtils\Scaffold\Controller;

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Snowflake\IdGeneratorInterface;
use Hyperf\Utils\Arr;
use Psr\Container\ContainerInterface;
use HyperfAdmin\BaseUtils\Constants\ErrorCode;
use HyperfAdmin\BaseUtils\Log;
use HyperfAdmin\Validation\Validation;

abstract class Controller
{
    public $check_login = true;

    public $check_resource = true;

    public $open_resources = [];

    /**
     * 容器
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * request对象
     *
     * @var RequestInterface
     */
    protected $request;

    /**
     * response对象
     *
     * @var ResponseInterface
     */
    protected $response;

    /**
     * 当前访问资源路径 path
     *
     * @var string
     */
    protected $resource;

    /**
     * @var IdGeneratorInterface
     */
    protected $idGen;

    /**
     * 校验组件
     *
     * @var Validation
     */
    protected $validation;

    /**
     * 权限校验组件
     *
     * @var
     */
    protected $permission;

    public function __construct(ContainerInterface $container, RequestInterface $request, ResponseInterface $response)
    {
        $this->container = $container;
        $this->request = $request;
        $this->response = $response;
        $this->resource = $this->getCalledSource();
        $this->idGen = $container->get(IdGeneratorInterface::class);
        $this->validation = make(Validation::class);

        if (method_exists($this, 'init')) {
            $this->init();
        }
    }

    protected function getCalledSource($get_arr = false)
    {
        $uri = $this->getRequestUri();
        $parts = array_filter(explode('/', $uri));
        if ($get_arr) {
            return array_values($parts);
        }
        return implode('.', $parts);
    }

    protected function getRequestUri()
    {
        $http_request = $this->container->get(RequestInterface::class);
        return $http_request->getServerParams()['request_uri'] ?? '';
    }

    /**
     * 返回成功的请求
     *
     * @param array  $data
     * @param string $message
     *
     * @return array
     */
    public function success(array $data = [], $message = '操作成功')
    {
        $response = [
            'code' => 0,
            'message' => $message,
            'payload' => $data ?: (object)[],
        ];
        Log::get('http.' . $this->getCalledSource())->info(0, $response);
        return $response;
    }

    /**
     * @param int         $code
     * @param string|null $message
     *
     * @return array
     */
    public function fail(int $code = -1, ?string $message = null)
    {
        $response = [
            'code' => $code,
            'message' => $message ?: ErrorCode::getMessage($code),
            'payload' => (object)[],
        ];
        Log::get('http.' . $this->getCalledSource())->info($code, $response);
        return $response;
    }
}
