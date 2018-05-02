<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common;


class File
{
    public static function asyncRead(string $filename, $callback, array $arguments = [])
    {
        try {
            if (file_exists($filename)) {
                $fileInfo = stat($filename);
                if ($fileInfo['size'] <= 8192) {
                    swoole_async_readfile($filename, function ($filename, $content) use ($callback, $arguments) {
                        array_unshift($arguments, $filename, $content);
                        if ((is_string($callback) && function_exists($callback)) || (is_object($callback) && is_callable($callback))) {
                            $callback(...$arguments);
                        } else if (is_array($callback) && count($callback) == 2) {
                            if (is_object($callback[0]) && is_callable([$callback[0], $callback[1]])) {
                                $object = $callback[0];
                                $method = $callback[1];
                                $object->$method(...$arguments);
                            } else if (is_string($callback[0]) && is_callable([$callback[0], $callback[1]])) {
                                $object = $callback[0];
                                $method = $callback[1];
                                $object::$method(...$arguments);
                            }
                        } else {
                            throw new \Exception('Arguments 2 is inaccessible');
                        }
                        unset($content);
                    });
                } else {
                    $fileContent = '';
                    swoole_async_read($filename, function ($filename, $content) use ($callback, $arguments, &$fileContent) {
                        if (null !== $content) {
                            $fileContent .= $content;
                            return true;
                        } else {
                            array_unshift($arguments, $filename, $fileContent);
                            if ((is_string($callback) && function_exists($callback)) || (is_object($callback) && is_callable($callback))) {
                                $callback(...$arguments);
                            } else if (is_array($callback) && count($callback) == 2) {
                                if (is_object($callback[0]) && is_callable([$callback[0], $callback[1]])) {
                                    $object = $callback[0];
                                    $method = $callback[1];
                                    $object->$method(...$arguments);
                                } else if (is_string($callback[0]) && is_callable([$callback[0], $callback[1]])) {
                                    $object = $callback[0];
                                    $method = $callback[1];
                                    $object::$method(...$arguments);
                                }
                            } else {
                                throw new \Exception('Arguments 2 is inaccessible');
                            }
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

    public static function asyncExec(string $command, $callback, array $arguments = [])
    {
        try {
            \Swoole\Async::exec($command, function ($result, $status) use ($callback, $arguments) {
                array_unshift($arguments, $result);
                if ((is_string($callback) && function_exists($callback)) || (is_object($callback) && is_callable($callback))) {
                    $callback(...$arguments);
                } else if (is_array($callback) && count($callback) == 2) {
                    if (is_object($callback[0]) && is_callable([$callback[0], $callback[1]])) {
                        $object = $callback[0];
                        $method = $callback[1];
                        $object->$method(...$arguments);
                    } else if (is_string($callback[0]) && is_callable([$callback[0], $callback[1]])) {
                        $object = $callback[0];
                        $method = $callback[1];
                        $object::$method(...$arguments);
                    }
                } else {
                    throw new \Exception('Arguments 2 is inaccessible');
                }
            });
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }
}