<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common;


class Inotify
{
    /**
     * 监听事件类型
     * @var int
     */
    protected static $mask = IN_CREATE | IN_DELETE | IN_MODIFY;

    /**
     * Inotify实例
     * @var null
     */
    protected static $_instance = null;

    /**
     * 初始化Inotify实例
     * @param int|null $mask
     * @return null|Inotify
     */
    public static function getInstance(int $mask = null)
    {
        self::$mask = is_null($mask) ? self::$mask : $mask;
        self::$_instance = is_null(self::$_instance) ? new self() : self::$_instance;

        return self::$_instance;
    }

    /**
     * 启用监听
     * @param array $pathname
     * @param callable|null $callback
     * @throws \Exception
     */
    public function watch($pathname = [], callable $callback = null)
    {
        if (!extension_loaded('inotify')) {
            throw new \Exception('inotify extension not loaded');
        }

        //创建inotify句柄
        $handle = inotify_init();

        //监听文件
        $pathname = is_string($pathname) ? [$pathname] : (is_array($pathname) ? $pathname : []);
        foreach ($pathname as $path) {
            if (file_exists($path)) {
                $watch_descriptor = inotify_add_watch($handle, $path, self::$mask);
            }
        }

        //加入监听回调底层时间轮询
        swoole_event_add($handle, function ($handle) use ($callback) {
            $events = inotify_read($handle);
            if ($events) {
                !is_null($callback) && $callback();
            }
        });
    }
}