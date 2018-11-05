<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common;


class Command
{
    /**
     * 获取命令对象实例
     * @param \swoole_server|null $server
     * @return \swoole_server|static
     */
    public static function getInstance(\swoole_server $server = null)
    {
        return is_null($server) ? new static() : $server;
    }

    /**
     * 重载指定服务工作进程
     * @param string $server_name
     * @param callable|null $callback
     * @throws \ReflectionException
     */
    public function reload(string $server_name = '', callable $callback = null)
    {
        if (is_string($server_name) && $server_name !== '') {
            $pidFile = VSWOOLE_DATA_PID_PATH . $server_name . '_Master' . VSWOOLE_PID_EXT;
            if (file_exists($pidFile)) {
                File::read($pidFile, function ($filename, $content) use ($callback) {
                    File::exec('kill -USR1 ' . $content, function ($result) use ($callback) {
                        !is_null($callback) && $callback();
                    });
                });
            }
        }
    }

    /**
     * 关闭指定服务
     * @param string $server_name
     * @param callable|null $callback
     * @throws \ReflectionException
     */
    public function shutdown(string $server_name = '', callable $callback = null)
    {
        if (is_string($server_name) && $server_name !== '') {
            $pidFile = VSWOOLE_DATA_PID_PATH . $server_name . '_Master' . VSWOOLE_PID_EXT;
            if (file_exists($pidFile)) {
                File::read($pidFile, function ($filename, $content) use ($callback) {
                    File::exec('kill -15 ' . $content, function ($result) use ($callback) {
                        !is_null($callback) && $callback();
                    });
                });
            }
        }
    }

    /**
     * 重载指定服务日志文件
     * @param string $server_name
     * @param callable|null $callback
     * @throws \ReflectionException
     */
    public function reloadLog(string $server_name = '', callable $callback = null)
    {
        if (is_string($server_name) && $server_name !== '') {
            $pidFile = VSWOOLE_DATA_PID_PATH . $server_name . '_Master' . VSWOOLE_PID_EXT;
            if (file_exists($pidFile)) {
                File::read($pidFile, function ($filename, $content) use ($callback) {
                    File::exec('kill -34 ' . $content, function ($result) use ($callback) {
                        !is_null($callback) && $callback();
                    });
                });
            }
        }
    }
}