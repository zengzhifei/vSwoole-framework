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

class Log
{
    /**
     * 异步记录日志
     * @param string $content
     * @param string $fileName
     * @param int $mode
     * @param callable|null $callback
     */
    public static function write(string $content = '', string $fileName = 'vSwoole.log', int $mode = FILE_APPEND, callable $callback = null)
    {
        if (VSWOOLE_IS_CLI) {
            $logFile = VSWOOLE_LOG_SERVER_PATH . date('Ym') . '/' . date('d') . '/' . $fileName;
            $content = '[' . date('Y-m-d H:i:s') . '] ' . PHP_EOL . $content . PHP_EOL . PHP_EOL;
            File::write($logFile, $content);
        } else {
            trigger_error('async-io method write only in cli mode');
        }
    }

    /**
     * 同步记录日志
     * @param string $content
     * @param string $fileName
     * @param int $mode
     * @return bool|int
     */
    public static function save($content = '', string $fileName = 'vSwoole.log', int $mode = FILE_APPEND)
    {
        $logDir = VSWOOLE_IS_CLI ? VSWOOLE_LOG_SERVER_PATH . date('Ym') . '/' . date('d') : VSWOOLE_LOG_CLIENT_PATH . date('Ym') . '/' . date('d');
        if (!file_exists($logDir)) {
            @mkdir($logDir, 755, true);
        }
        $logFile = $logDir . '/' . $fileName;
        $content = '[' . date('Y-m-d H:i:s') . '] ' . PHP_EOL . $content . PHP_EOL . PHP_EOL;
        return @file_put_contents($logFile, $content, $mode);
    }
}