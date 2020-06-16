<?php
namespace HyperfAdmin\DataFocus\Util;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ValidatorVisitor extends NodeVisitorAbstract
{
    public $sandbox;

    public function __construct(PHPSandbox $sandbox)
    {
        $this->sandbox = $sandbox;
    }

    public function leaveNode(Node $node)
    {
        if($node instanceof Node\Stmt\Function_) {
            $this->sandbox->filterFunction($node);
        }
        if($node instanceof Node\Expr\FuncCall) {
            $this->sandbox->filterFuncCall($node);
        }
        if($node instanceof Node\Stmt\Class_) {
            $this->sandbox->filterClass($node);
        }
        if($node instanceof Node\Stmt\InlineHTML) {
            throw new SandboxException('not allowed InlineHTML');
        }
    }
}
