<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\application\client;


use vSwoole\library\client\TimerClient;
use vSwoole\library\common\Config;
use vSwoole\library\common\Exception;
use vSwoole\library\common\Redis;
use vSwoole\library\common\Request;
use vSwoole\library\common\Response;

class Timer extends TimerClient
{
    /**
     * 连接服务器
     * @param array $connectOptions
     * @param array $configOptions
     */
    public function __construct(array $connectOptions = [], array $configOptions = [])
    {
        parent::__construct($connectOptions, $configOptions);
    }

    /**
     * 获取定时任务列表
     */
    public function getTaskList()
    {
        try {
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            $task_list = $redis->hGetAll(Config::loadConfig('redis')->get('redis_key.Timer.Task_List'));
            if ($task_list) {
                foreach ($task_list as $key => $task) {
                    $task_list[$key] = json_decode($task, true);
                }
                Response::return(['status' => 1, 'msg' => 'success', 'data' => $task_list]);
            } else {
                Response::return(['status' => 0, 'msg' => 'failed']);
            }
        } catch (\Exception $e) {
            Exception::reportError($e);
        }
    }

    /**
     * 添加定时任务
     */
    public function addTask()
    {
        $task_url = Request::getInstance()->param('task_url', null);
        $task_num = Request::getInstance()->param('task_num', 1);
        $task_time = Request::getInstance()->param('task_time', 100);
        $task_name = Request::getInstance()->param('task_name', null);

        if (null === $task_url) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_url is empty']);
        } else if ('' === $task_url) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_url is invalid']);
        }

        $res = $this->execute('add', ['task_url' => $task_url, 'task_num' => $task_num, 'task_time' => $task_time, 'task_name' => $task_name]);
        if ($res) {
            Response::return(['status' => 1, 'msg' => 'success']);
        } else {
            Response::return(['status' => 0, 'msg' => 'failed']);
        }
    }

    /**
     * 开始定时任务
     */
    public function startTask()
    {
        $task_key = Request::getInstance()->param('task_key', null);

        if (null === $task_key) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_key is empty']);
        } else if ('' === $task_key) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_key is invalid']);
        }

        $res = $this->execute('start', ['task_key' => $task_key]);
        if ($res) {
            Response::return(['status' => 1, 'msg' => 'success']);
        } else {
            Response::return(['status' => 0, 'msg' => 'failed']);
        }
    }

    /**
     * 暂停定时任务
     */
    public function stopTask()
    {
        $task_key = Request::getInstance()->param('task_key', null);

        if (null === $task_key) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_key is empty']);
        } else if ('' === $task_key) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_key is invalid']);
        }

        $res = $this->execute('stop', ['task_key' => $task_key]);
        if ($res) {
            Response::return(['status' => 1, 'msg' => 'success']);
        } else {
            Response::return(['status' => 0, 'msg' => 'failed']);
        }
    }

    /**
     * 删除定时任务
     */
    public function deleteTask()
    {
        $task_key = Request::getInstance()->param('task_key', null);

        if (null === $task_key) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_key is empty']);
        } else if ('' === $task_key) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_key is invalid']);
        }

        $res = $this->execute('delete', ['task_key' => $task_key]);
        if ($res) {
            Response::return(['status' => 1, 'msg' => 'success']);
        } else {
            Response::return(['status' => 0, 'msg' => 'failed']);
        }
    }
}