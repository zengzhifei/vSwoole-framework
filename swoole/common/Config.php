<?php
/**
 * 配置工具类
 * User: zengz
 * Date: 2018/4/19
 * Time: 17:19
 */

namespace swoole\common;


class Config
{
    //配置
    private static $configs = [];
    //单例
    private static $_instance = null;

    /**
     * 载入配置文件
     * @param string $configName
     * @return null|Config
     */
    public static function loadConfig(string $configName = '*')
    {
        if (defined('SWOOLE_CONFIG_PATH') && is_dir(SWOOLE_CONFIG_PATH)) {
            $configFiles = glob(SWOOLE_CONFIG_PATH . $configName . '.php');
            foreach ($configFiles as $configFile) {
                $configInfo = pathinfo($configFile);
                $configs[$configInfo['filename']] = require_once $configFile;
            }
            if ($configName !== '*') {
                self::$configs = $configName == '*' ? $configs : $configs[$configName];
            }
        }
        if (self::$_instance) {
            return self::$_instance;
        } else {
            return new self();
        }
    }

    /**
     * 获取配置
     * @param string|null $configKey
     * @return array|mixed|null
     */
    public function get(string $configKey = null)
    {
        if ($configKey) {
            if (!empty(self::$configs)) {
                if (false === stripos($configKey, '.')) {
                    return isset(self::$configs[$configKey]) ? self::$configs[$configKey] : null;
                } else {
                    $configKeys = explode('.', $configKey);
                    $config = self::$configs;
                    foreach ($configKeys as $configKey) {
                        if (isset($config[$configKey])) {
                            $config = $config[$configKey];
                        } else {
                            break;
                        }
                    }
                    return $config;
                }
            } else {
                return null;
            }
        } else {
            return self::$configs;
        }
    }
}