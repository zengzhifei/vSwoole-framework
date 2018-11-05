<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\application\client;


use vSwoole\core\client\CrontabClient;
use vSwoole\library\common\Config;
use vSwoole\library\common\cache\Redis;
use vSwoole\library\common\Request;
use vSwoole\library\common\Response;

class Crontab extends CrontabClient
{
    /**
     * 连接服务器
     * Crontab constructor.
     * @param array $connectOptions
     * @param array $configOptions
     * @throws \Exception
     */
    public function __construct(array $connectOptions = [], array $configOptions = [])
    {
        if (empty($connectOptions)) {
            $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
            $serverKey = Config::loadConfig('redis')->get('redis_key.Crontab.Server_Ip');
            $server_ips = $redis->SMEMBERS($serverKey);
            if ($server_ips) {
                $connect_status = false;
                foreach ($server_ips as $ip) {
                    $connect_status = parent::connect(['host' => $ip], $configOptions) == false ? $connect_status || false : $connect_status || true;
                }
            } else {
                $connect_status = parent::connect($connectOptions, $configOptions);
            }
        } else {
            $connect_status = parent::connect($connectOptions, $configOptions);
        }
        if (false == $connect_status) {
            Response::return(['status' => 504, 'msg' => 'Server Connect Gateway Timeout']);
        }
    }

    /**
     * 获取服务器列表
     * @throws \Exception
     */
    public function getServerList()
    {
        $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
        $server_ips = $redis->SMEMBERS(Config::loadConfig('redis')->get('redis_key.Crontab.Server_Ip'));
        $connect_servers = $this->getConnectIp();
        if ($server_ips) {
            foreach ($server_ips as $server_ip) {
                $server_list[] = [
                    'server_ip'     => $server_ip,
                    'server_status' => in_array($server_ip, $connect_servers) ? 1 : 0
                ];
            }
        }
        if (isset($server_list)) {
            Response::return(['status' => 1, 'msg' => 'get success', 'data' => $server_list]);
        } else {
            Response::return(['status' => 0, 'msg' => 'get failed']);
        }
    }

    /**
     * 重启服务
     */
    public function reload()
    {
        $server_ip = Request::getInstance()->param('server_ip', null);
        $res = $this->execute('reload', [], $server_ip);
        if ($res) {
            Response::return(['status' => 1, 'msg' => 'reload success']);
        } else {
            Response::return(['status' => 0, 'msg' => 'reload failed']);
        }
    }

    /**
     * 关闭服务
     */
    public function shutdown()
    {
        $server_ip = Request::getInstance()->param('server_ip', null);
        $res = $this->execute('shutdown', [], $server_ip);
        if ($res) {
            Response::return(['status' => 1, 'msg' => 'shutdown success']);
        } else {
            Response::return(['status' => 0, 'msg' => 'shutdown failed']);
        }
    }

    /**
     * 清理异常服务器
     * @throws \Exception
     */
    public function clearServers()
    {
        $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
        $server_key = Config::loadConfig('redis')->get('redis_key.Crontab.Server_Ip');
        $server_ips = $redis->SMEMBERS($server_key);
        $connect_servers = $this->getConnectIp();
        if ($server_ips) {
            foreach ($server_ips as $server_ip) {
                if (!in_array($server_ip, $connect_servers)) {
                    $redis->sRem($server_key, $server_ip);
                }
            }
        }
        Response::return(['status' => 1, 'msg' => 'clear success']);
    }

    /**
     * 添加定时任务
     */
    public function addTask()
    {
        $task_id = Request::getInstance()->param('task_id', '');
        $task_cmd = Request::getInstance()->param('task_cmd', '');
        $task_url = Request::getInstance()->param('task_url', '');
        $task_time = Request::getInstance()->param('task_time', '');
        $task_process_num = Request::getInstance()->param('task_process_num', 1);
        $task_concurrent_num = Request::getInstance()->param('task_concurrent_num', 1);
        $task_group = Request::getInstance()->param('task_group', '');
        $task_name = Request::getInstance()->param('task_name', '');

        if (null === $task_cmd) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_cmd is empty']);
        } else if ('' === $task_cmd) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_cmd is invalid']);
        }
        if (null === $task_url) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_url is empty']);
        } else if ('' === $task_url) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_url is invalid']);
        }
        if (null === $task_time) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_time is empty']);
        } else if ('' === $task_time) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_time is invalid']);
        }
        if (null === $task_process_num) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_process_num is empty']);
        } else if ('' === $task_process_num || $task_process_num < 1) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_process_num is invalid']);
        }
        if (null === $task_concurrent_num) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_concurrent_num is empty']);
        } else if ('' === $task_concurrent_num || $task_concurrent_num < 1) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_concurrent_num is invalid']);
        }
        if (null === $task_group) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_group is empty']);
        } else if ('' === $task_group) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_group is invalid']);
        }
        if (null === $task_name) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_name is empty']);
        } else if ('' === $task_name) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_name is invalid']);
        }

        $data = ['task_id' => $task_id, 'task_cmd' => $task_cmd, 'task_url' => $task_url, 'task_process_num' => $task_process_num, 'task_concurrent_num' => $task_concurrent_num, 'task_time' => $task_time, 'task_group' => $task_group, 'task_name' => $task_name];
        $res = $this->execute('add', $data);
        if ($res) {
            Response::return(['status' => 1, 'msg' => 'success']);
        } else {
            Response::return(['status' => 0, 'msg' => 'failed']);
        }
    }

    /**
     * 获取定时任务列表
     */
    public function getTaskList()
    {
        $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
        if ($task_list = $redis->hGetAll(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'))) {
            foreach ($task_list as $key => $task) {
                $task_list[$key] = json_decode($task, true);
            }
            Response::return(['status' => 1, 'msg' => 'success', 'data' => $task_list]);
        } else {
            Response::return(['status' => 0, 'msg' => 'failed']);
        }
    }

    /**
     * 获取定时任务
     */
    public function getTask()
    {
        $task_id = Request::getInstance()->param('task_id', '');

        if (null === $task_id) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_id is empty']);
        } else if ('' === $task_id) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_id is invalid']);
        }

        $redis = Redis::getInstance(Config::loadConfig('redis')->get('redis_master'), true);
        if ($task = $redis->hGet(Config::loadConfig('redis')->get('redis_key.Crontab.Task_List'), $task_id)) {
            Response::return(['status' => 1, 'msg' => 'success', 'data' => json_decode($task)]);
        } else {
            Response::return(['status' => 0, 'msg' => 'failed']);
        }
    }

    /**
     * 开始定时任务
     */
    public function startTask()
    {
        $task_id = Request::getInstance()->param('task_id', null);

        if (null === $task_id) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_id is empty']);
        } else if ('' === $task_id) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_id is invalid']);
        }

        $res = $this->execute('start', ['task_id' => $task_id]);
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
        $task_id = Request::getInstance()->param('task_id', null);

        if (null === $task_id) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_id is empty']);
        } else if ('' === $task_id) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_id is invalid']);
        }

        $res = $this->execute('stop', ['task_id' => $task_id]);
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
        $task_id = Request::getInstance()->param('task_id', null);

        if (null === $task_id) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_id is empty']);
        } else if ('' === $task_id) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_id is invalid']);
        }

        $res = $this->execute('delete', ['task_id' => $task_id]);
        if ($res) {
            Response::return(['status' => 1, 'msg' => 'success']);
        } else {
            Response::return(['status' => 0, 'msg' => 'failed']);
        }
    }
}