<?php
namespace HyperfAdmin\RuleEngine\Context;

interface ContextPluginInterface
{
    public function name(): string;

    public function keys(): array;
}
