<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common\kafka;


use RdKafka\Producer;
use RdKafka\TopicConf;

class ProducerTopic
{
    protected $topic = null;

    /**
     * ProducerTopic constructor.
     * @param Producer $producer
     * @param string $topic
     * @param TopicConf|null $topicConf
     */
    public function __construct(Producer $producer, string $topic, TopicConf $topicConf = null)
    {
        $this->topic = $producer->newTopic($topic, $topicConf);
    }

    /**
     * 生产消息
     * @param string $message
     */
    public function produce(string $message = '')
    {
        $this->topic->produce(RD_KAFKA_PARTITION_UA, 0, $message);
    }
}