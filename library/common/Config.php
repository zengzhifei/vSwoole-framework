<?php
/**
 * 配置工具类
 * User: zengzhifei
 * Date: 2018/4/19
 * Time: 17:19
 */

namespace vSwoole\library\common;

class Config
{
    //配置
    private static $configs = [];
    //单例
    private static $_instance = null;

    /**
     * 装载配置文件
     * @param string $config_file
     * @return Config|null
     */
    public static function loadConfig(string $config_file = '*')
    {
        if (defined('VSWOOLE_CONFIG_PATH') && is_dir(VSWOOLE_CONFIG_PATH)) {
            $configFiles = glob(VSWOOLE_CONFIG_PATH . $config_file . VSWOOLE_CONFIG_EXT);
            $configs = [];
            foreach ($configFiles as $configFile) {
                $configInfo = pathinfo($configFile);
                $configs[$configInfo['filename']] = require $configFile;
            }
            self::$configs = $config_file == '*' ? $configs : $configs[$config_file];
        }

        self::$_instance = self::$_instance ? self::$_instance : new self();
        return self::$_instance;
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