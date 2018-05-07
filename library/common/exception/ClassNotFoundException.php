<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common\exception;


use Throwable;

class ClassNotFoundException extends Exception
{
    public function __construct(string $message = "", int $code = E_WARNING, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}