<?php
namespace HyperfAdmin\DataFocus\Util;

use PhpParser\Error;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

class PHPSandbox
{
    private $allow_functions = [
        'print',
        'var_dump',
        'printf',
        'sprintf',
        'json_encode',
        'json_decode',
        'count',
        'array',
        'sizeof',
        'in_array',
        'is_array',
        'is_bool',
        'is_numeric',
        'is_string',
        'trim',
        'number_format',
        'date',
        'time',
        'strtotime',
        'implode',
        'explode',
        'substr',
        'preg_match',
        'preg_match_all',
        'preg_split',
        'preg_replace',
        'parse_url',
        'parse_str',
        'http_build_query',
        'round',
        'floatval',
        'intval',
        'ceil',
        'floor',
        'rand',
        'abs',
        'usort',
        'uasort',
        'uksort',
        'sort',
        'asort',
        'arsort',
        'ksort',
        'krsort',
        'mb_strlen',
        'mb_substr',
        'md5',
        'base64_encode',
        'base64_decode',
        'min',
        'max',
        'extract',
    ];

    private $define_function = [];

    private $function_validator;

    private $namespace;

    public function execute($code)
    {
        if(preg_match('/<\?(?:php|=)(.*)?\?>/msui', $code, $match)) {
            $code = trim($match[1]);
        }
        if($this->namespace) {
            $code = sprintf("namespace %s {\n%s\n}", $this->namespace, $code);
        }
        $this->validateCode(sprintf("<?php %s", $code));
        try {
            return eval($code);
        } catch (\Exception $exception) {
            throw new SandboxException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
        }
    }

    public function parserCode($code)
    {
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        try {
            return $parser->parse($code);
        } catch (Error $error) {
            throw new SandboxException($error->getMessage(), $error->getCode(), $error->getPrevious());
        }
    }

    public function setAllowFunctions($name)
    {
        $this->allow_functions = array_merge($this->allow_functions, (array)$name);

        return $this;
    }

    public function setFunctionValidator(callable $callable)
    {
        $this->function_validator = $callable;

        return $this;
    }

    public function validateCode($code)
    {
        $parser = $this->parserCode($code);
        $traverser = new NodeTraverser();
        $validator = new ValidatorVisitor($this);
        $traverser->addVisitor($validator);
        $traverser->traverse($parser);

        return $parser;
    }

    public function filterFunction(Function_ $parserFunction, $namespace = '')
    {
        $function = $parserFunction->name->toString();
        if($this->namespace) {
            $namespace = $this->namespace;
        }
        $name = $namespace ? "$namespace\\$function" : $function;
        if(in_array($name, $this->define_function)) {
            throw new SandboxException(sprintf('previously redeclare function [%s]', $function));
        }
        $this->validatorFunc($function);
        $this->define_function[] = $name;
    }

    public function filterFuncCall(FuncCall $call, $namespace = '')
    {
        $call = $call->name->toString();
        $this->validatorFunc($call);
    }

    public function filterClass(Class_ $parserClass)
    {
        $class = $parserClass->name->toString();
        throw new SandboxException(sprintf('define class [%s] not allowed!', $class));
    }

    public function validatorFunc($name)
    {
        if(in_array($name, $this->allow_functions)) {
            return true;
        }
        if(!$this->function_validator) {
            return true;
        }
        if(!call_user_func_array($this->function_validator, [$name, $this])) {
            throw new SandboxException(sprintf('Function [%s] not allowed!', $name));
        }
    }

    public function functionDefined($name)
    {
        return in_array($name, $this->define_function);
    }

    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }
}
