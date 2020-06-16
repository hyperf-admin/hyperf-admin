<?php
namespace HyperfAdmin\RuleEngine\Context;

class RequestContext extends ContextPluginAbstract
{
    public function name(): string
    {
        return 'request';
    }
}
