<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common\kafka;


use vSwoole\library\common\exception\ErrorException;

class GroupConsumer
{
    protected $options = [
        'broker_list' => '127.0.0.1:9092',
        'group_id'    => 0,
        'timeout'     => 120e3
    ];

    protected $consumer;

    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);

        if (empty($this->options['broker_list'])) {
            new ErrorException('The argument broker_list is invalid');
        }

        if (!class_exists('\RdKafka\Consumer')) {
            throw new \Exception('Not support: rdkafka');
        }

        $topicConf = new \RdKafka\TopicConf();
        $topicConf->set('auto.offset.reset', 'smallest');

        $conf = new \RdKafka\Conf();
        $conf->set('group.id', $this->options['group_id']);
        $conf->set('metadata.broker.list', $this->options['broker_list']);
        $conf->setDefaultTopicConf($topicConf);

        $this->consumer = new \RdKafka\KafkaConsumer($conf);
    }

    /**
     * 消费消息
     * @param array $topics
     * @param callable|null $callback
     * @throws \RdKafka\Exception
     */
    public function consume(array $topics = [], callable $callback = null)
    {
        $this->consumer->subscribe($topics);

        while (true) {
            if ($message = $this->consumer->consume($this->options['timeout'])) {
                switch ($message->err) {
                    case RD_KAFKA_RESP_ERR_NO_ERROR:
                        call_user_func_array($callback, [$message->payload]);
                        break;
                }
            }
            sleep(1);
        }
    }
}