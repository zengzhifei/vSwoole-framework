<?php
// +----------------------------------------------------------------------+
// | VSwoole FrameWork                                                    |
// +----------------------------------------------------------------------+
// | Not Decline To Shoulder a Responsibility                             |
// +----------------------------------------------------------------------+
// | zengzhifei@outlook.com                                               |                  
// +----------------------------------------------------------------------+

//
/**
 * 服务+别名注册
 * defined('VSWOOLE_SERVERNAME_SERVER') or define('VSWOOLE_SERVERNAME_SERVER', 'Swoole_SERVERNAME_Server') and define('Servername', VSWOOLE_SERVERNAME_SERVER);
 */

//Http服务
defined('VSWOOLE_HTTP_SERVER') or define('VSWOOLE_HTTP_SERVER', 'Swoole_Http_Server') and define('Http', VSWOOLE_HTTP_SERVER);
//UDP服务
defined('VSWOOLE_UDP_SERVER') or define('VSWOOLE_UDP_SERVER', 'Swoole_Udp_Server') and define('Udp', VSWOOLE_UDP_SERVER);
//WebSocket服务
defined('VSWOOLE_WEB_SOCKET_SERVER') or define('VSWOOLE_WEB_SOCKET_SERVER', 'Swoole_WebSocket_Server') and define('WebSocket', VSWOOLE_WEB_SOCKET_SERVER);
//Crontab服务
defined('VSWOOLE_CRONTAB_SERVER') or define('VSWOOLE_CRONTAB_SERVER', 'Swoole_Crontab_Server') and define('Crontab', VSWOOLE_CRONTAB_SERVER);
// Kafka服务
defined('VSWOOLE_KAFKA_SERVER') or define('VSWOOLE_KAFKA_SERVER', 'Swoole_Kafka_Server') and define('Kafka', VSWOOLE_KAFKA_SERVER);