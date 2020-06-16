<?php declare(strict_types=1);
namespace HyperfAdmin\BaseUtils;

use Psr\Container\ContainerInterface;

class StdoutLoggerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return Log::get('default');
    }
}
