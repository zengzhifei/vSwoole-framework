<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common;


use vSwoole\library\common\exception\Exception;

class Timer
{
    /**
     * 间隔时钟定时器
     * @param int $ms
     * @param callable|null $callback
     */
    public static function tick(int $ms = 0, callable $callback = null)
    {
        if ($ms > 0 && !is_null($callback)) {
            swoole_timer_tick($ms, function ($timer_id) use ($callback) {
                $callback($timer_id);
            });
        } else {
            throw new \InvalidArgumentException('Arguments invalid');
        }
    }

    /**
     * 延迟时钟定时器
     * @param int $ms
     * @param callable|null $callback
     */
    public static function after(int $ms = 0, callable $callback = null)
    {
        if ($ms > 0 && !is_null($callback)) {
            swoole_timer_after($ms, function () use ($callback) {
                $callback();
            });
        } else {
            throw new \InvalidArgumentException('Arguments invalid');
        }
    }

    /**
     * 清除定时器
     * @param int $timer_id
     */
    public static function clear(int $timer_id)
    {
        if ($timer_id > 0) {
            swoole_timer_clear($timer_id);
        } else {
            throw new \InvalidArgumentException('Arguments invalid');
        }
    }
}