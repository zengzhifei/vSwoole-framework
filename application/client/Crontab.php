<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\application\client;


use vSwoole\library\client\CrontabClient;
use vSwoole\library\common\Config;
use vSwoole\library\common\exception\Exception;
use vSwoole\library\common\cache\Redis;
use vSwoole\library\common\Request;
use vSwoole\library\common\Response;

class Crontab extends CrontabClient
{
    /**
     * 连接服务器
     * Timer constructor.
     * @param array $connectOptions
     * @param array $configOptions
     * @throws \ReflectionException
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
            $task_list = $redis->hGetAll(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'));
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
        $task_cmd = Request::getInstance()->param('task_cmd', '');
        $task_url = Request::getInstance()->param('task_url', '');
        $task_name = Request::getInstance()->param('task_name', '');
        $task_number = Request::getInstance()->param('task_number', 1);
        $task_time = Request::getInstance()->param('task_time', 100);

        if (null === $task_url) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_url is empty']);
        } else if ('' === $task_url) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_url is invalid']);
        }

        $data = ['task_cmd' => $task_cmd, 'task_url' => $task_url, 'task_name' => $task_name, 'task_number' => $task_number, 'task_time' => $task_time];
        $res = $this->execute('add', $data);
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