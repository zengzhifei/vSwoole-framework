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
     * @param int $flags
     * @param callable|null $callback
     */
    public static function write(string $content = '', string $fileName = 'vSwoole.log', int $flags = FILE_APPEND, callable $callback = null)
    {
        $logDir = VSWOOLE_IS_CLI ? VSWOOLE_LOG_SERVER_PATH . '/' . date('Ym') . '/' . date('d') : VSWOOLE_LOG_CLIENT_PATH . '/' . date('Ym') . '/' . date('d');
        if (!is_dir($logDir)) {
            mkdir($logDir, 755, true);
        }
        $logFile = $logDir . '/' . $fileName;
        $content = '[' . date('Y-m-d H:i:s') . '] ' . PHP_EOL . $content . PHP_EOL;
        if (mb_strlen($content, 'utf-8') >= 4194304) {
            swoole_async_write($logFile, $content, -1, $callback);
        } else {
            swoole_async_writefile($logFile, $content, $callback, $flags);
        }
    }
}