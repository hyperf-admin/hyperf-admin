<?php

declare(strict_types=1);
namespace HyperfAdmin\EventBus;

class PipeMessage
{
    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
}

