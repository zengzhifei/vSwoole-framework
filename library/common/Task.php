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

class Task
{
    /**
     * 执行异步任务投递
     * @param \swoole_server $server
     * @param $callback
     * @param array $arguments
     * @param int $dst_worker_id
     * @throws \ReflectionException
     */
    public static function task(\swoole_server $server, $callback, array $arguments = [], int $dst_worker_id = -1)
    {
        try {
            if ((is_string($callback) && function_exists($callback)) || (is_object($callback) && is_callable($callback))) {
                $data = ['method' => $callback, 'arguments' => $arguments];
            } else if (is_array($callback) && count($callback) == 2) {
                if (is_object($callback[0]) && is_callable([$callback[0], $callback[1]])) {
                    $data = ['object' => $callback[0], 'method' => $callback[1], 'arguments' => $arguments];
                } else if (is_string($callback[0]) && is_callable([$callback[0], $callback[1]])) {
                    $data = ['object' => $callback[0], 'method' => $callback[1], 'arguments' => $arguments];
                }
            } else {
                throw new \Exception('Arguments 2 is inaccessible');
            }
            isset($data) && $server->task($data, $dst_worker_id);
        } catch (\Exception $e) {
            Exception::reportException($e);
        }
    }

    /**
     * 执行异步任务处理
     * @param \swoole_server $server
     * @param array $data
     */
    public static function execute(\swoole_server $server, array $data = [])
    {
        if (!isset($data['object']) && isset($data['method'])) {
            $method = $data['method'];
            $finish_data = $method(...$data['arguments']);
        } else if (isset($data['object']) && isset($data['method'])) {
            $object = $data['object'];
            $method = $data['method'];
            if (is_object($object)) {
                $finish_data = $object->$method(...$data['arguments']);
            } else if (is_string($object)) {
                $finish_data = $object::$method(...$data['arguments']);
            }
        }

        if (isset($finish_data) && $finish_data !== null) {
            if (is_array($finish_data)) {
                if ((is_string($finish_data[0]) && function_exists($finish_data[0])) || (is_object($finish_data[0]) && is_callable($finish_data[0]))) {
                    $data = ['method' => $finish_data[0]];
                } else if (is_array($finish_data[0]) && count($finish_data[0]) == 2) {
                    if (is_object($finish_data[0][0]) && is_callable([$finish_data[0][0], $finish_data[0][1]])) {
                        $data = ['object' => $finish_data[0][0], 'method' => $finish_data[0][1]];
                    } else if (is_string($finish_data[0][0]) && is_callable([$finish_data[0][0], $finish_data[0][1]])) {
                        $data = ['object' => $finish_data[0][0], 'method' => $finish_data[0][1]];
                    }
                }
                if (isset($data)) {
                    if (isset($finish_data[1])) {
                        $data['arguments'] = is_array($finish_data[1]) ? $finish_data[1] : [$finish_data[1]];
                    } else {
                        $data['arguments'] = [];
                    }
                    $server->finish($data);
                }
            }
        }
    }

    /**
     * 执行异步任务执行完成回调
     * @param array $data
     */
    public static function finish(array $data = [])
    {
        if (!isset($data['object']) && isset($data['method'])) {
            $method = $data['method'];
            $method(...$data['arguments']);
        } else if (isset($data['object']) && isset($data['method'])) {
            $object = $data['object'];
            $method = $data['method'];
            if (is_object($object)) {
                $object->$method(...$data['arguments']);
            } else if (is_string($object)) {
                $object::$method(...$data['arguments']);
            }
        }
    }
}