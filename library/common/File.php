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
    public static function write(string $filename, string $content = '', int $mode = FILE_APPEND, callable $callback = null)
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
     * 设置文件缓存
     * @param string $key
     * @param null $value
     * @param string $prefix
     * @param int $expire
     * @return bool|int
     * @throws \ReflectionException
     */
    public static function set(string $key, $value = null, string $prefix = '', int $expire = 0)
    {
        try {
            $file_name = $prefix . md5($key) . VSWOOLE_CACHE_FILE_EXT;
            if (is_null($value)) {
                return @unlink(VSWOOLE_DATA_CACHE_PATH . $file_name);
            } else {
                $data = ['expire' => $expire == 0 ? 0 : time() + $expire, 'value' => $value];
                $content = '<?php' . PHP_EOL . '//' . serialize($data) . PHP_EOL;
                return file_put_contents(VSWOOLE_DATA_CACHE_PATH . $file_name, $content);
            }
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 获取文件缓存
     * @param string $key
     * @param string $prefix
     * @return mixed|null
     * @throws \ReflectionException
     */
    public static function get(string $key, $prefix = '')
    {
        $file_name = $prefix . md5($key) . VSWOOLE_CACHE_FILE_EXT;
        $file = VSWOOLE_DATA_CACHE_PATH . $file_name;
        if (file_exists($file)) {
            $content = substr(file_get_contents($file), 8);
            $content = $content ? unserialize($content) : [];
            if ($content['expire'] != 0 && time() > $content['expire']) {
                self::set($key, null, $prefix);
                return null;
            } else {
                return isset($content['value']) ? $content['value'] : null;
            }
        } else {
            return null;
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