<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common\kafka;

use RdKafka\Consumer;
use RdKafka\TopicConf;

class ConsumerTopic
{
    protected $topic = null;

    /**
     * ConsumerTopic constructor.
     * @param Consumer $consumer
     * @param string $topic
     * @param TopicConf|null $topicConf
     */
    public function __construct(Consumer $consumer, string $topic, TopicConf $topicConf = null)
    {
        $this->topic = $consumer->newTopic($topic, $topicConf);
    }

    /**
     * 消费消息
     * @param callable|null $callback
     * @param int $timeout
     */
    public function consume(callable $callback = null, int $timeout = 120 * 1000)
    {
        $this->topic->consumeStart(0, RD_KAFKA_OFFSET_BEGINNING);

        while (true) {
            if ($message = $this->topic->consume(0, $timeout)) {
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