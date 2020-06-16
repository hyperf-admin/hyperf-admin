<?php
declare(strict_types=1);
namespace HyperfAdmin\BaseUtils;

use Dotenv\Dotenv;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Crontab\LoggerInterface;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\Utils\Str;
use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;
use HyperfAdmin\BaseUtils\Exception\HttpExceptionHandler;
use HyperfAdmin\BaseUtils\Listener\BootAppConfListener as HABootAppConfListener;
use HyperfAdmin\BaseUtils\Listener\DbQueryExecutedListener;
use HyperfAdmin\BaseUtils\Listener\FetchModeListener;
use HyperfAdmin\BaseUtils\Middleware\CorsMiddleware;
use HyperfAdmin\BaseUtils\Middleware\HttpLogMiddleware;

class ConfigProvider
{
    public function __invoke(): array
    {
        if (is_dev()) {
            $logger_default = [
                'handler' => [
                    'class' => HAStreamHandler::class,
                    'constructor' => [
                        'level' => Logger::INFO,
                        'stream' => 'php://stdout',
                    ],
                ],
                'formatter' => [
                    'class' => ColorLineFormatter::class,
                    'constructor' => [
                        'format' => "%datetime%||%channel%||%level_name%||%message%||%context%||%extra%\n",
                        'allowInlineLineBreaks' => true,
                        'includeStacktraces' => true,
                    ],
                ],
            ];
        } else {
            $logger_default = [
                'handler' => [
                    'class' => RotatingFileHandler::class,
                    'constructor' => [
                        'filename' => BASE_PATH . '/runtime/logs/app.log',
                        'maxFiles' => 1,
                        'level' => Logger::INFO,
                    ],
                ],
                'formatter' => [
                    'class' => JsonFormatter::class,
                    'constructor' => [],
                ],
            ];
        }

        return [
            'commands' => [],
            'dependencies' => [
                // 终端彩色日志
                StdoutLoggerInterface::class => StdoutLoggerFactory::class,
                // 处理 routes 分文件路由
                DispatcherFactory::class => RoutesDispatcher::class,
            ],
            'listeners' => [
                HABootAppConfListener::class,
                DbQueryExecutedListener::class,
                FetchModeListener::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'logger' => [
                'default' => $logger_default,
            ],
            'middlewares' => [
                'http' => [
                    CorsMiddleware::class,
                    HttpLogMiddleware::class,
                ],
            ],
            'redis' => [
                'metric' => [
                    'host' => env('REDIS_ALERT_MANAGER_HOST', 'localhost'),
                    'auth' => env('REDIS_ALERT_MANAGER_AUTH', null),
                    'port' => (int)env('REDIS_ALERT_MANAGER_PORT', 6379),
                    'db' => (int)env('REDIS_ALERT_MANAGER_DB', 0),
                    'pool' => [
                        'min_connections' => 1,
                        'max_connections' => 10,
                        'connect_timeout' => 10.0,
                        'wait_timeout' => 3.0,
                        'heartbeat' => -1,
                        'max_idle_time' => (float)env('REDIS_ALERT_MANAGER_MAX_IDLE_TIME', 60),
                    ],
                ],
            ],
            'init_routes' => [
                __DIR__ . '/config/routes.php',
            ],
            'exceptions' => [
                'handler' => [
                    'http' => [
                        HttpExceptionHandler::class
                    ],
                ],
            ],
            'publish' => [],
        ];
    }
}
