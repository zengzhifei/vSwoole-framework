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
     * 重启指定服务
     * @param string $server_name
     * @throws \ReflectionException
     */
    public function reload(string $server_name = '')
    {
        if (is_string($server_name) && $server_name !== '') {
            $pidFile = VSWOOLE_DATA_PID_PATH . $server_name . '_Master' . VSWOOLE_PID_EXT;
            if (file_exists($pidFile)) {
                File::read($pidFile, function ($filename, $content) {
                    File::exec('kill -USR1 ' . $content, function ($result) {
                    });
                });
            }
        }
    }

    /**
     * 关闭指定服务
     * @param string $server_name
     * @throws \ReflectionException
     */
    public function shutdown(string $server_name = '')
    {
        if (is_string($server_name) && $server_name !== '') {
            $pidFile = VSWOOLE_DATA_PID_PATH . $server_name . '_Master' . VSWOOLE_PID_EXT;
            if (file_exists($pidFile)) {
                File::read($pidFile, function ($filename, $content) {
                    File::exec('kill 15 ' . $content, function ($result) {
                    });
                });
            }
        }
    }
}