<?php
declare(strict_types=1);

use Hyperf\AsyncQueue\Driver\DriverFactory;
use HyperfAdmin\AlertManager\AlertJob;

function alert_message($message)
{
    return container(DriverFactory::class)->get('alert_manager')->push(new AlertJob($message));
}
