<?php
declare(strict_types=1);
namespace Hyperf\Config;

use Hyperf\Utils\Composer;
use function class_exists;
use function is_string;
use function method_exists;

/**
 * 此 ProviderConfig 为未替换 hyperf 原生的扩展包配置加载类
 * 增加 扩展的 权重 特性
 */
class ProviderConfig
{
    /**
     * @var array
     */
    private static $providerConfigs = [];

    public static function load(): array
    {
        if (!static::$providerConfigs) {
            $providers = Composer::getMergedExtra('hyperf')['config'] ?? [];
            $handel = function ($arr) {
                return array_map(function ($item) {
                    $explode = explode('@', $item);
                    return [
                        'provider' => $explode[0],
                        'weight' => $explode[1] ?? 0,
                    ];
                }, $arr);
            };
            $package = $handel($providers);
            array_change_v2k($package, 'provider');

            $local = json_decode(file_get_contents(BASE_PATH . '/composer.json'), true)['extra']['hyperf']['config'] ?? [];
            $local = $handel($local);
            array_change_v2k($local, 'provider');

            foreach ($local as $key => $val) {
                $package[$key] = $val;
            }

            $providers = array_values($package);
            usort($providers, function ($a, $b) {
                return $a['weight'] > $b['weight'];
            });
            $providers = array_column($providers, 'provider');
            static::$providerConfigs = static::loadProviders($providers);
        }
        return static::$providerConfigs;
    }

    public static function clear(): void
    {
        static::$providerConfigs = [];
    }

    protected static function loadProviders(array $providers): array
    {
        $providerConfigs = [];
        foreach ($providers as $provider) {
            if (is_string($provider) && class_exists($provider) && method_exists($provider, '__invoke')) {
                $providerConfigs[] = (new $provider())();
            }
        }

        return static::merge(...$providerConfigs);
    }

    protected static function merge(...$arrays): array
    {
        $result = array_merge_recursive(...$arrays);
        if (isset($result['dependencies'])) {
            $dependencies = array_column($arrays, 'dependencies');
            $result['dependencies'] = array_merge(...$dependencies);
        }

        return $result;
    }
}
