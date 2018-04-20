<?php
/**
 * 杂类工具类
 * User: zengz
 * Date: 2018/4/20
 * Time: 2:02
 */

namespace library\common;

class Utils
{
    /**
     * 异步记录日志
     * @param string $content
     * @param string $fileName
     * @param callable|null $callback
     * @param int $flags
     */
    public static function asyncLog(string $content = '', int $type = VSWOOLE_SERVER, string $fileName = 'library.log', callable $callback = null, int $flags = FILE_APPEND)
    {
        $logDir = $type == VSWOOLE_SERVER ? VSWOOLE_SERVER_LOG_PATH . '/' . date('Ymd') : VSWOOLE_CLIENT_LOG_PATH . '/' . date('Ymd');
        if (!is_dir($logDir)) {
            mkdir($logDir, 755, true);
        }
        $logFile = $logDir . $fileName;
        $content = '[' . date('Y-m-d H:i:s') . '] ' . $content . PHP_EOL;
        swoole_async_writefile($logFile, $content, $callback, $flags);
    }

    /**
     * 获取本机服务器Ip地址
     * @return array
     */
    public static function getServerIp()
    {
        return swoole_get_local_ip();
    }
}