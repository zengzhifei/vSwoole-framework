<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |
// +----------------------------------------------------------------------+

namespace vSwoole\library\common\kafka;


use RdKafka\TopicConf;
use vSwoole\library\common\exception\ErrorException;

class Producer
{
    /**
     * @var array
     */
    protected $options = [
        'broker_list' => '127.0.0.1:9092',
        'log_level'   => LOG_DEBUG
    ];

    /**
     * @var array
     */
    protected $topic_instance = [];

    /**
     * @var \RdKafka\Producer
     */
    protected $producer;

    /**
     * Producer constructor.
     * @param array $options
     * @throws \Exception
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);

        if (empty($this->options['broker_list'])) {
            new ErrorException('The argument broker_list is invalid');
        }

        if (!class_exists('\RdKafka\Producer')) {
            throw new \Exception('Not support: rdkafka');
        }

        $this->producer = new \RdKafka\Producer();
        $this->producer->setLogLevel($this->options['log_level']);
        $this->producer->addBrokers($this->options['broker_list']);
    }

    /**
     * 选取topic
     * @param string $topic
     * @param TopicConf|null $topicConf
     * @return mixed
     */
    public function topic(string $topic = '', TopicConf $topicConf = null)
    {
        $instance = md5($topic);

        if (!isset($this->topic_instance[$instance])) {
            $this->topic_instance[$instance] = new ProducerTopic($this->producer, $topic, $topicConf);
        }

        return $this->topic_instance[$instance];
    }
}