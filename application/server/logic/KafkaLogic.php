<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\application\server\logic;


use vSwoole\library\common\kafka\Consumer;
use vSwoole\library\common\kafka\GroupConsumer;
use vSwoole\library\common\kafka\Producer;
use vSwoole\library\common\Timer;

class KafkaLogic
{
    /**
     * KafkaLogic constructor.
     * @param \swoole_server $server
     */
    public function __construct(\swoole_server $server)
    {
        $GLOBALS['server'] = $server;

        $this->assign();
    }

    public function assign()
    {
        if ($GLOBALS['server']->taskworker) {
            if ($GLOBALS['server']->worker_id == ($GLOBALS['server']->setting['worker_num'] + 0)) {
                $this->consume();
            } else if ($GLOBALS['server']->worker_id == ($GLOBALS['server']->setting['worker_num'] + 1)) {
                $this->groupConsume();
            } else {
                $this->produce();
            }
        }
    }

    public function consume()
    {
        $consumer = new Consumer([
            'broker_list' => '47.95.198.4:9092'
        ]);
        $topic = $consumer->topic('produce_consume_group_consume');
        $topic->consume(function ($message) {
            var_dump('consume:' . $message);
        });
    }

    public function groupConsume()
    {
        $consumer = new GroupConsumer([
            'broker_list' => '47.95.198.4:9092'
        ]);
        $consumer->consume(['produce_consume_group_consume'], function ($message) {
            var_dump('group_consume:' . $message);
        });
    }

    public function produce()
    {
        $producer = new Producer([
            'broker_list' => '47.95.198.4:9092',
            'partition'   => 0,
        ]);
        $topic = $producer->topic('produce_consume_group_consume');
        Timer::tick(3000, function () use ($topic) {
            $data = ['name' => rand(0, 100), 'time' => time()];
            $topic->produce(json_encode($data));
        });
    }
}