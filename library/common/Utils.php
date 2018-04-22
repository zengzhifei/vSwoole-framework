<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace library\common;

class Utils
{
    /**
     * 获取本机服务器Ip地址
     * @return array
     */
    public static function getServerIp()
    {
        return swoole_get_local_ip();
    }

    /**
     * 异步记录服务主进程PID
     * @param int $pid
     * @param string $pidName
     */
    public static function writePid(int $pid = 0, string $pidName = '')
    {
        if ($pidName) {
            if (!is_dir(VSWOOLE_DATA_PID_PATH)) {
                mkdir(VSWOOLE_DATA_PID_PATH, 755, true);
            }
            $pidFile = VSWOOLE_DATA_PID_PATH . $pidName . VSWOOLE_PID_EXT;
            swoole_async_writefile($pidFile, $pid);
        }
    }
}