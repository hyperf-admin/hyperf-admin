<?php
namespace HyperfAdmin\DevTools;

use Hyperf\Utils\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Helpers;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;

abstract class AbstractMaker
{
    protected function getMethod(\ReflectionMethod $from)
    {
        $start_line = $from->getStartLine();
        $end_line = $from->getEndLine();
        $length = $end_line - $start_line;
        $source = file($from->getFileName());
        $body = trim(implode("", array_slice($source, $start_line, $length)));
        if(Str::startsWith($body, '{')) {
            $body = Str::replaceFirst('{', '', $body);
        }
        $body = Str::replaceLast('}', '', $body);
        $body = str::replaceArray('        ', [''], $body);
        $method = new Method($from->getName());
        $method->setParameters(array_map([
            $this,
            'fromParameterReflection',
        ], $from->getParameters()));
        $method->setStatic($from->isStatic());
        $isInterface = $from->getDeclaringClass()->isInterface();
        $method->setVisibility($from->isPrivate() ? ClassType::VISIBILITY_PRIVATE : ($from->isProtected() ? ClassType::VISIBILITY_PROTECTED : ($isInterface ? null : ClassType::VISIBILITY_PUBLIC)));
        $method->setFinal($from->isFinal());
        $method->setAbstract($from->isAbstract() && !$isInterface);
        $method->setBody($from->isAbstract() ? null : $body);
        $method->setReturnReference($from->returnsReference());
        $method->setVariadic($from->isVariadic());
        $method->setComment(Helpers::unformatDocComment((string)$from->getDocComment()));
        if($from->getReturnType() instanceof \ReflectionNamedType) {
            $method->setReturnType($from->getReturnType()->getName());
            $method->setReturnNullable($from->getReturnType()->allowsNull());
        }

        return $method;
    }

    public function fromParameterReflection(\ReflectionParameter $from): Parameter
    {
        $param = new Parameter($from->getName());
        $param->setReference($from->isPassedByReference());
        $param->setType($from->getType() instanceof \ReflectionNamedType ? $from->getType()
            ->getName() : null);
        $param->setNullable($from->hasType() && $from->getType()->allowsNull());
        if($from->isDefaultValueAvailable()) {
            $param->setDefaultValue($from->isDefaultValueConstant() ? new Literal($from->getDefaultValueConstantName()) : $from->getDefaultValue());
            $param->setNullable($param->isNullable() && $param->getDefaultValue() !== null);
        }

        return $param;
    }

    protected function pathToNamespace(string $path): string
    {
        return implode('\\', array_map(function ($item) {
            return ucfirst($item);
        }, explode('/', $path)));
    }

    public function getBaseClass($save_path, $class_namespace, $class_name, $extend_class = null)
    {
        $namespace = new PhpNamespace($class_namespace);
        $extend_class && $namespace->addUse($extend_class);
        if(!file_exists($save_path)) {
            $dir = dirname($save_path);
            if(!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $class = $namespace->addClass($class_name);
            if($extend_class) {
                $class->addExtend($extend_class);
            }
        } else {
            $class = ClassType::from($class_namespace . '\\' . $class_name);
            $namespace->add($class);
            $ref = new \ReflectionClass($class_namespace . '\\' . $class_name);
            $methods = $ref->getMethods();
            foreach($methods as $key => $item) {
                $methods[$item->name] = $item;
                unset($methods[$key]);
            }
            $parent_methods = $ref->getParentClass()->getMethods();
            foreach($parent_methods as $key => $item) {
                $parent_methods[$item->name] = $item;
                unset($parent_methods[$key]);
            }
            $diff = array_diff(array_keys($methods), array_keys($parent_methods));
            $custom_methods = [];
            foreach($diff as $name) {
                $custom_methods[] = $this->getMethod($methods[$name]);
            }
            $class->setMethods($custom_methods);
        }

        return [$namespace, $class];
    }

    public function getNamespaceCode($namespace)
    {
        $print = new PsrPrinter();

        return "<?php \ndeclare(strict_types = 1);\n" . $print->printNamespace($namespace);
    }

    public function arrayStr($arr, $deep = 1)
    {
        $str = "[\n";
        foreach($arr as $key => $item) {
            $k = is_string($key) ? "'{$key}' => " : '';
            if(is_array($item)) {
                $val = $this->arrayStr($item, $deep + 1);
                $str .= str_repeat(' ', $deep * 4) . "{$k}{$val},\n";
            } else {
                $val = is_bool($item) ? ($item ? 'true' : 'false') : (is_string($item) ? "'{$item}'" : $item);
                $str .= str_repeat(' ', $deep * 4) . "{$k}{$val},\n";
            }
        }
        $str .= str_repeat(' ', ($deep - 1) * 4) . "]";

        return $str;
    }
}
