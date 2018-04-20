<?php
/**
 * 杂类工具类
 * User: zengz
 * Date: 2018/4/20
 * Time: 2:02
 */

namespace swoole\common;


class Utils
{
    /**
     * 异步记录日志
     * @param string $content
     * @param string $fileName
     * @param callable|null $callback
     * @param int $flags
     */
    public static function asyncLog(string $content = '', string $fileName = 'swoole.log', callable $callback = null, int $flags = FILE_APPEND)
    {
        $logFile = SWOOLE_LOG_PATH . $fileName;
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