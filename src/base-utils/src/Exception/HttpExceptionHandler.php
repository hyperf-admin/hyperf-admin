<?php

declare(strict_types=1);
namespace HyperfAdmin\BaseUtils\Exception;

use Hyperf\Di\Annotation\Inject;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Psr\Http\Message\ResponseInterface;
use HyperfAdmin\BaseUtils\Constants\ErrorCode;
use HyperfAdmin\BaseUtils\Log;
use Throwable;

class HttpExceptionHandler extends ExceptionHandler
{
    /**
     * @Inject()
     * @var \Hyperf\HttpServer\Contract\ResponseInterface
     */
    protected $response;

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        Log::get('http.exception')->error($throwable->getCode(), [
            'trace' => (string)$throwable,
        ]);
        if (is_production()) {
            return $this->response(ErrorCode::CODE_ERR_SYSTEM, '服务器内部错误');
        }

        return $this->response($throwable->getCode() ?: ErrorCode::CODE_ERR_SYSTEM, (string)$throwable);
    }

    /**
     * 抽取函数方便子类重写response结构
     *
     * @param int    $code
     * @param string $msg
     *
     * @return ResponseInterface
     */
    protected function response($code, $msg)
    {
        return $this->response->json([
            'code' => $code,
            'msg' => $msg,
        ]);
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}

