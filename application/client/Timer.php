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

    public function addTask()
    {
        $task_url = Request::getInstance()->param('task_url', null);

        if (null === $task_url) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_url is empty']);
        } else if ('' === $task_url) {
            Response::return(['status' => -1, 'msg' => 'Arguments task_url is invalid']);
        }

        $res = $this->execute('add', ['task_url' => $task_url]);
        if ($res) {
            Response::return(['status' => 1, 'msg' => 'add success']);
        } else {
            Response::return(['status' => 0, 'msg' => 'add failed']);
        }
    }

}