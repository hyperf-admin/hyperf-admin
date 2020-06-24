<?php
declare(strict_types=1);
namespace HyperfAdmin\BaseUtils\Middleware;

use FastRoute\Dispatcher;
use Hyperf\HttpMessage\Exception\NotFoundHttpException;
use Hyperf\HttpServer\Router\Dispatched;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use HyperfAdmin\BaseUtils\Log;

class HttpLogMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start_time = microtime(true);
        $path = $request->getUri()->getPath();
        $uri = $request->getRequestTarget();
        $referer = $request->getServerParams()['remote_addr'] ?? '';
        $referer .= $request->getHeader('referer')[0] ?? '';
        $uriEx = [
            '/',
            '/ping',
            '/gw-heart',
            '/consul-heart',
            '/favicon.ico',
        ];
        $request_msg = [
            'uri' => $uri,
            'header' => json_encode($request->getHeaders()),
            'request' => mb_substr($request->getBody()->getContents(), 0, 125),
        ];
        if(!in_array($path, $uriEx)) {
            Log::get('http')->info('start', $request_msg);
        }
        $response = $handler->handle($request);
        /** @var Dispatched $dispatched */
        $dispatched = $request->getAttribute(Dispatched::class);
        switch($dispatched->status) {
            case Dispatcher::NOT_FOUND:
                Log::get('http')->warning(sprintf('%s not found', $path));
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                Log::get('http')
                    ->warning(sprintf('%s method %s not allowed', $path, $request->getMethod()));
                break;
            case Dispatcher::FOUND:
                if(!in_array($path, $uriEx)) {
                    $response_content = mb_substr($response->getBody()->getContents(), 0, 125);
                    $msg = [
                        'referer' => $referer,
                        'uri' => $request->getRequestTarget(),
                        'request' => $request->getBody()->getContents(),
                        'use_time' => 1000 * (microtime(true) - $start_time),
                        'response' => $response_content,
                    ];
                    Log::get('http')->info('resume', $msg);
                }
                break;
        }

        return $response;
    }
}
