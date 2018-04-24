<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

namespace vSwoole\library\common;


class Log
{
    /**
     * 异步记录日志
     * @param string $content
     * @param string $fileName
     * @param int $type
     * @param int $flags
     * @param callable|null $callback
     */
    public static function write(string $content = '', string $fileName = 'vSwoole.log', int $type = VSWOOLE_SERVER, int $flags = FILE_APPEND, callable $callback = null)
    {
        $logDir = $type == VSWOOLE_SERVER ? VSWOOLE_SERVER_LOG_PATH . '/' . date('Ymd') : VSWOOLE_CLIENT_LOG_PATH . '/' . date('Ymd');
        if (!is_dir($logDir)) {
            mkdir($logDir, 755, true);
        }
        $logFile = $logDir . $fileName;
        $content = '[' . date('Y-m-d H:i:s') . '] ' . PHP_EOL;
        $content .= $content . PHP_EOL . PHP_EOL;
        if (mb_strlen($content, 'utf-8') >= 4194304) {
            swoole_async_write($logFile, $content, -1, $callback);
        } else {
            swoole_async_writefile($logFile, $content, $callback, $flags);
        }
    }
}