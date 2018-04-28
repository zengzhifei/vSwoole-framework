<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

namespace vSwoole\library\client;


use vSwoole\library\common\Config;
use vSwoole\library\common\Exception;

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

    /**
     * 连接服务器
     * @param array $connectOptions
     * @param array $configOptions
     */
    public function __construct(array $connectOptions = [], array $configOptions = [])
    {
        try {
            $connectOptions = array_merge(Config::loadConfig('websocket')->get('ws_client_connect'), $connectOptions);
            $configOptions = array_merge(Config::loadConfig('websocket')->get('ws_client_config'), $configOptions);
            if (false !== parent::__construct($connectOptions, $configOptions)) {
                $this->key = $this->generateToken();
                $this->header = $this->createHeader($connectOptions['host'], $connectOptions['port']);
                $this->client->send($this->header);
                $res = $this->recv();
                if ($res) {
                    $this->clients_instance[md5($connectOptions['host'])] = $this->client;
                    return $this->client;
                }
                return false;
            } else {
                $this->client->close();
                throw new \Exception('Swoole Client connect failed');
            }
        } catch (\Exception $e) {
            Exception::reportError($e);
        }
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
     * @return mixed
     */
    private function recv()
    {
        try {
            $data = $this->client->recv();
            if ($data === false) {
                throw new \Exception("swoole_websocket_server return false.");
            }
            $this->buffer .= $data;
            $recv_data = $this->parseData($this->buffer);
            if ($recv_data) {
                $this->buffer = '';
                return $recv_data;
            } else {
                throw new \Exception("swoole_websocket_server return failed.");
            }
        } catch (\Exception $e) {
            Exception::reportError($e);
        }
    }

    /**
     * 格式化响应数据包
     * @param $response
     * @return bool
     */
    private function parseData($response)
    {
        try {
            if (!$this->connected) {
                $response = $this->parseIncomingRaw($response);
                if (isset($response['Sec-Websocket-Accept']) && base64_encode(pack('H*', sha1($this->key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11'))) === $response['Sec-Websocket-Accept']) {
                    $this->connected = true;
                    return true;
                } else {
                    throw new \Exception("error response key.");
                }
            }

            $frame = \swoole_websocket_server::unpack($response);
            if ($frame) {
                return $this->returnData ? $frame->data : $frame;
            } else {
                throw new \Exception("swoole_websocket_server::unpack failed.");
            }
        } catch (\Exception $e) {

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
     * @return bool
     */
    public function execute(string $cmd = '', array $data = [])
    {
        if ($cmd && is_string($cmd)) {
            $result = true;
            $data['cmd'] = $cmd;
            if (!empty($this->clients_instance)) {
                foreach ($this->clients_instance as $client) {
                    if ($client->isConnected()) {
                        $res = $client->send(\swoole_websocket_server::pack(json_encode($data), WEBSOCKET_OPCODE_TEXT));
                        $res = false === $res ? false : true;
                        $return_status = $this->parseData($client->recv());
                        $result = $result && $res && $return_status->finish;
                    }
                }
            } else {
                $result = $result && false;
            }
            return $result;
        } else {
            return false;
        }
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