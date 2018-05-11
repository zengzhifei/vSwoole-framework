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
            $server->task(['callback' => $callback, 'arguments' => $arguments], $dst_worker_id);
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
        $finish_data = call_user_func_array($data['callback'], $data['arguments']);
        if ($finish_data && is_array($finish_data) && isset($finish_data[0])) {
            $server->finish(['callback' => $finish_data[0], 'arguments' => $finish_data[1] ?? []]);
        }
    }

    /**
     * 执行异步任务执行完成回调
     * @param array $data
     */
    public static function finish(array $data = [])
    {
        call_user_func_array($data['callback'], $data['arguments']);
    }
}