<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

namespace vSwoole\core\client;


use vSwoole\library\client\Client;
use vSwoole\library\common\Config;

class WebSocketClient extends Client
{
    //客户端版本号
    const VERSION = '0.1.4';
    //token 长度
    const TOKEN_LENGTH = 16;

    //连接包
    protected $key;
    protected $header;
    protected $path = '/';
    protected $origin = null;
    protected $buffer = '';
    //客户端连接状态
    protected $connected = false;
    //服务端返回数据包
    protected $returnData = false;
    //客户端实例
    protected $clients_instance = [];
    //连接实例
    protected $connect_instance = [];

    /**
     * 连接服务器
     * @param array $connectOptions
     * @param array $configOptions
     * @return bool|\swoole_client
     */
    public function connect(array $connectOptions = [], array $configOptions = [])
    {
        $connectOptions = array_merge(Config::loadConfig('websocket')->get('client_connect'), $connectOptions);
        $configOptions = array_merge(Config::loadConfig('websocket')->get('client_config'), $configOptions);
        if (false !== parent::connect($connectOptions, $configOptions)) {
            $this->key = $this->generateToken();
            $this->header = $this->createHeader($connectOptions['host'], $connectOptions['port']);
            $this->client->send($this->header);
            if ($this->recv()) {
                $this->clients_instance[md5($connectOptions['host'])] = $this->client;
                $this->connect_instance[md5($connectOptions['host'])] = $connectOptions['host'];
                return $this->client;
            }
        } else {
            $this->client->close();
        }
        return false;
    }

    /**
     * 生成websocket 握手协议Sec-WebSocket-Key
     * @return string
     */
    private function generateToken()
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"§$%&/()=[]{}';
        $useChars = array();
        for ($i = 0; $i < self::TOKEN_LENGTH; $i++) {
            $useChars[] = $characters[mt_rand(0, strlen($characters) - 1)];
        }
        array_push($useChars, rand(0, 9), rand(0, 9), rand(0, 9));
        shuffle($useChars);
        $randomString = trim(implode('', $useChars));
        $randomString = substr($randomString, 0, self::TOKEN_LENGTH);

        return base64_encode($randomString);
    }

    /**
     * 生成WebSocket客户端连接Header
     * @param string $host
     * @param int $port
     * @return string
     */
    private function createHeader(string $host, int $port)
    {
        $host = $host === '127.0.0.1' || $host === '0.0.0.0' ? 'localhost' : $host;

        return "GET {$this->path} HTTP/1.1" . "\r\n" .
            "Origin: {$this->origin}" . "\r\n" .
            "Host: {$host}:{$port}" . "\r\n" .
            "Sec-WebSocket-Key: {$this->key}" . "\r\n" .
            "User-Agent: swoole-client/" . self::VERSION . "\r\n" .
            "Upgrade: websocket" . "\r\n" .
            "Connection: Upgrade" . "\r\n" .
            "Sec-WebSocket-Protocol: chat, superchat" . "\r\n" .
            "Sec-WebSocket-Version: 13" . "\r\n" . "\r\n";
    }

    /**
     * 接收服务器返回消息
     * @return bool
     */
    private function recv()
    {
        if ($data = $this->client->recv()) {
            $this->buffer .= $data;
            if ($recv_data = $this->parseData($this->buffer)) {
                $this->buffer = '';
                return $recv_data;
            }
        }
        return false;
    }

    /**
     * 格式化响应数据包
     * @param $response
     * @return bool
     */
    private function parseData($response)
    {
        if (!$this->connected) {
            $response = $this->parseIncomingRaw($response);
            if (isset($response['Sec-Websocket-Accept']) && base64_encode(pack('H*', sha1($this->key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11'))) === $response['Sec-Websocket-Accept']) {
                $this->connected = true;
                return true;
            } else {
                return false;
            }
        } else {
            if ($frame = \swoole_websocket_server::unpack($response)) {
                return $this->returnData ? $frame->data : $frame;
            } else {
                return false;
            }
        }
    }

    /**
     * 格式化数据
     * @param $header
     * @return array
     */
    private function parseIncomingRaw($header)
    {
        $retval = array();
        $content = "";
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
        foreach ($fields as $field) {
            if (preg_match('/([^:]+): (.+)/m', $field, $match)) {
                $match[1] = preg_replace_callback('/(?<=^|[\x09\x20\x2D])./',
                    function ($matches) {
                        return strtoupper($matches[0]);
                    },
                    strtolower(trim($match[1])));
                if (isset($retval[$match[1]])) {
                    $retval[$match[1]] = array($retval[$match[1]], $match[2]);
                } else {
                    $retval[$match[1]] = trim($match[2]);
                }
            } else {
                if (preg_match('!HTTP/1\.\d (\d)* .!', $field)) {
                    $retval["status"] = $field;
                } else {
                    $content .= $field . "\r\n";
                }
            }
        }
        $retval['content'] = $content;
        return $retval;
    }

    /**
     * 向服务器发送指令+数据
     * @param string $cmd
     * @param array $data
     * @param string|null $server_ip
     * @return bool
     */
    public function execute(string $cmd = '', array $data = [], string $server_ip = null)
    {
        if ($cmd && is_string($cmd) && !empty($this->clients_instance)) {
            $send_data = ['cmd' => $cmd, 'data' => $data];
            if (empty($server_ip)) {
                foreach ($this->clients_instance as $ip => $client) {
                    if ($client->isConnected()) {
                        if ($client->send(\swoole_websocket_server::pack(json_encode($send_data), WEBSOCKET_OPCODE_TEXT))) {
                            $return_status = $this->parseData($client->recv());
                            if ($return_status && $return_status->finish) {
                                $result[$this->connect_instance[$ip]] = $return_status->data;
                            }
                        }
                    }
                }
            } else if (array_key_exists(md5($server_ip), $this->clients_instance)) {
                $client = $this->clients_instance[md5($server_ip)];
                if ($client->isConnected()) {
                    if ($client->send(\swoole_websocket_server::pack(json_encode($send_data), WEBSOCKET_OPCODE_TEXT))) {
                        $return_status = $this->parseData($client->recv());
                        if ($return_status && $return_status->finish) {
                            $result[$server_ip] = $return_status->data;
                        }
                    }
                }
            }
        }
        return $result ?? false;
    }

    /**
     * 发送心跳
     * @param string|null $server_ip
     */
    public function ping(string $server_ip = null)
    {
        if (empty($server_ip)) {
            foreach ($this->clients_instance as $ip => $client) {
                if ($client->isConnected()) {
                    $client->send(\swoole_websocket_server::pack('', 9));
                }
            }
        } else if (array_key_exists(md5($server_ip), $this->clients_instance)) {
            $client = $this->clients_instance[md5($server_ip)];
            if ($client->isConnected()) {
                $client->send(\swoole_websocket_server::pack('', 9));
            }
        }
    }

    /**
     * 获取已连接IP实例
     * @return array
     */
    public function getConnectIp()
    {
        return $this->connect_instance;
    }

    /**
     * 请求结束，关闭客户端连接
     */
    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        foreach ($this->clients_instance as $client) {
            if ($client->isConnected()) {
                $client->close();
            }
        }
    }
}