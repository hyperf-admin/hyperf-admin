<?php
declare(strict_types=1);
namespace HyperfAdmin\BaseUtils\Constants;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;

/**
 * @Constants
 * @method static string getMessage(int $code)
 */
class ErrorCode extends AbstractConstants
{
    /**
     * @Message("Fail")
     */
    const FAIL = -1;

    /**
     * @Message("ok")
     */
    const CODE_SUCC = 0;

    /**
     * @Message("err other")
     */
    const CODE_ERR_OTHER = 1;

    /**
     * @Message("用户不存在")
     */
    const CODE_ERR_AUTH = 100;

    /**
     * @Message("err auth")
     */
    const CODE_ERR_AUTH_USERNAME = 101;

    /**
     * @Message("err auth")
     */
    const CODE_ERR_AUTH_PASSWORD = 102;

    /**
     * @Message("err auth")
     */
    const CODE_ERR_AUTH_DISABLE = 103;

    /**
     * @Message("err auth")
     */
    const CODE_ERR_AUTH_VERIFICATION_CODE = 104;

    /**
     * @Message("err auth")
     */
    const CODE_ERR_AUTH_SECOND_VERIFICATION = 105;

    /**
     * @Message("err auth")
     */
    const CODE_ERR_DUPLICATE = 201;

    /**
     * @Message("err auth")
     */
    const CODE_ERR_REDIRECT = 302;

    /**
     * @Message("err auth")
     */
    const CODE_ERR_UNAUTHORIZED = 401;

    /**
     * @Message("err auth")
     */
    const CODE_ERR_DENY = 403;

    /**
     * @Message("err auth")
     */
    const CODE_ERR_NOT_FOUND = 404;

    /**
     * @Message("err auth")
     */
    const CODE_ERR_SYSTEM = 500;

    /**
     * @Message("err auth")
     */
    const CODE_ERR_SERVER = 502;

    /**
     * @Message("参数错误")
     */
    const CODE_ERR_PARAM = 600;

    /**
     * @Message("err auth")
     */
    const CODE_ERR_PARAM_MISSING = 601;

    /**
     * @Message("err auth")
     */
    const CODE_ERR_PARAM_INVALID = 602;

    /**
     * @Message("err auth")
     */
    const CODE_ERR_PARAM_EXPIRE = 603;

    /**
     * @Message("err auth")
     */
    const CODE_ERR_PARAM_SIGNATURE = 604;

    /**
     * @Message("重新登录")
     */
    const CODE_LOGIN = 401100;

    /**
     * @Message("权限不足")
     */
    const CODE_NO_AUTH = 403100;
}
