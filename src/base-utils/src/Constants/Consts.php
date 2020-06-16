<?php
namespace HyperfAdmin\BaseUtils\Constants;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;

/**
 * @Constants
 */
class Consts extends AbstractConstants
{
    const DEFAULT_MODEL_NAMESPACE = 'App\\Model';

    const YES = 1;

    const NO = 0;

    const DATETIME_FORMAT = 'Y-m-d H:i:s.u';

    const VERSION_MIN = '1.6.1';

    const PLATFORM_WECHAT = 2;

    const PLATFORM_WEB = 3;

    const PLATFORM_WXA = 4;

    const PLATFORM_IOS = 30;

    const PLATFORM_ANDROID = 50;

    const PLATFORM_QTT = 110;

    public static $platforms = [
        self::PLATFORM_WECHAT => 'wechat',
        self::PLATFORM_WEB => 'web',
        self::PLATFORM_WXA => 'mapp',
        self::PLATFORM_IOS => 'ios',
        self::PLATFORM_ANDROID => 'android',
        self::PLATFORM_QTT => 'qtt',
    ];
}
