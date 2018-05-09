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

class File
{
    /**
     * 异步读取文件
     * @param string $filename
     * @param callable $callback
     * @throws \ReflectionException
     */
    public static function read(string $filename, callable $callback)
    {
        try {
            if (file_exists($filename)) {
                $fileInfo = stat($filename);
                if ($fileInfo['size'] <= 8192) {
                    swoole_async_readfile($filename, function ($filename, $content) use ($callback) {
                        $callback($filename, $content);
                        unset($content);
                    });
                } else {
                    $fileContent = '';
                    swoole_async_read($filename, function ($filename, $content) use ($callback, &$fileContent) {
                        if (null !== $content) {
                            $fileContent .= $content;
                            return true;
                        } else {
                            $callback($filename, $fileContent);
                            unset($fileContent);
                            return false;
                        }
                    });
                }
            } else {
                throw new \Exception("File {$filename} is not exists");
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 异步写入文件
     * @param string $filename
     * @param string $content
     * @param int $mode
     * @param callable|null $callback
     */
    public static function write(string $filename, string $content = '', int $mode = 0, callable $callback = null)
    {
        $pathInfo = pathinfo($filename);
        if (!file_exists($pathInfo['dirname'])) {
            @mkdir($pathInfo['dirname'], 755, true);
        }
        if (mb_strlen($content, 'utf-8') >= 4194304) {
            swoole_async_write($filename, $content, -1, $callback);
        } else {
            swoole_async_writefile($filename, $content, $callback, $mode);
        }
    }

    /**
     * 同步读取文件内容
     * @param string $filename
     * @param callable|null $callback
     */
    public static function get(string $filename, callable $callback = null)
    {
        if (file_exists($filename)) {
            if (false !== ($file_content = file_get_contents($filename))) {
                !is_null($callback) && $callback($file_content);
            }
        }
    }

    /**
     * 同步写入文件内容
     * @param string $filename
     * @param string $content
     * @param int $mode
     * @param callable|null $callback
     */
    public static function put(string $filename, string $content = '', int $mode = 0, callable $callback = null)
    {
        $pathInfo = pathinfo($filename);
        if (!file_exists($pathInfo['dirname'])) {
            @mkdir($pathInfo['dirname'], 755, true);
        }
        if (file_put_contents($filename, $content, $mode)) {
            is_null($callback) && $callback();
        }
    }

    /**
     * 异步执行命令
     * @param string $command
     * @param $callback
     * @param array $arguments
     * @throws \ReflectionException
     */
    public static function exec(string $command, callable $callback = null)
    {
        try {
            \Swoole\Async::exec($command, function ($result, $status) use ($callback) {
                !is_null($callback) && $callback($result, $status);
            });
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }
}