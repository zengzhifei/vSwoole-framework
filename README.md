# vSwoole
vSwoole是对Swoole扩展的超轻量级封装，主要用于PHP微型服务器开发，暂且叫做框架，实际与完善的框架差很多。
框架已有服务：WebSocket，Http，Udp，Timer定时器等。
框架汲取大多数Web框架可用的特点，尽量减少服务器开发与Web开发的不同。又保持服务器开发的一些必须要求。
框架特点：
+ 服务配置管理缓存化，加快配置读取
+ 对象静态化，加快对象访问
+ 对象单例化，减少内存消耗
+ 管理客户端与用户客户端以端口区分连接，服务接口也以端口区分，加强安全性
+ 异步文件IO与同步文件IO功能组件化，以函数区别调用
+ 异步与同步缓存操作组件化，以参数区别调用
+ 服务配置文件分离，便于管理
+ WebSocket服务支持分布式
+ Timer定时器服务，异步任务毫秒数定时处理
+ Task，Process等扩展底层调用方法封装成组件，便于使用
+ 服务管理，如重启，关闭，启动等，可通过管理客户端调用服务接口，也可通过命令行操作
+ 支持命令行构建服务核心类文件，节省开发时间
+ 应用基础层，逻辑代码与服务代码分离，可通过平滑重启工作进程，对修改的逻辑代码重新加载
+ 服务对外以接口方式提供服务和管理
+ 框架可独立使用，可作为组件被其他框架引入
+ 命令行提供：install（安装目录），clear（清理日志），build（构建服务核心文件），start（启动指定服务），reload（重启指定服务），shutdown（关闭指定服务），help（帮助）等命令

## 环境要求：
> PHP7.0以上

> swoole扩展2.0以上

> hiredis扩展

## 目录结构

~~~
vSwoole（服务框架目录）
├─application                   框架应用根目录
│   │
│   ├─client                    客户端应用目录
│   │   ├─logic                 客户端应用逻辑目录
│   │   ├─ClientName.php        客户端应用基础层
│   │   └─···              
│   └─server                    服务端应用目录
│       ├─logic                 服务端应用逻辑目录
│       ├─ServerName.php        服务端应用基础层
│       └─···              
│
├─configs                       框架配置根目录
│   │ 
│   ├─convention.php            偏好配置
│   ├─config.php                基础配置
│   ├─redis.php                 缓存配置
│   ├─db.php                    数据库配置
│   ├─server.php                基础服务配置
│   └─···                       
│
├─data                          框架数据根目录
│   │ 
│   └─pid                       服务进程PID目录
│       ├─Server_Manager.pid    服务管理进程PID
│       ├─Server_Master.pid     服务主进程PID
│       └─···                   
│
├─library                       框架核心目录
│   │
│   ├─client                    客户端核心目录
│   │   ├─Client.php            客户端底层抽象模型
│   │   ├─ServerClient.php      客户端核心层
│   │   └─···
│   │─server                    服务端核心目录
│   │   ├─Server.php            服务端底层抽象模型
│   │   ├─ServerServer.php      服务端核心层
│   │   └─···
│   ├─common                    核心工具类目录
│   │   ├─Build.php             服务核心层构建类
│   │   ├─Command.php           服务管理命令类
│   │   ├─Config.php            服务配置类
│   │   ├─Exception.php         服务异常处理类
│   │   ├─File.php              服务文件处理类
│   │   ├─Log.php               服务日志处理类
│   │   ├─Process.php           服务进程管理类
│   │   ├─Redis.php             服务缓存处理类
│   │   ├─Request.php           服务客户端请求处理类
│   │   ├─Respinse.php          服务客户端响应处理类
│   │   ├─Task.php              服务异步任务处理类
│   │   ├─Utils.php             服务其他工具类
│   │   └─···
│   ├─conf                      框架核心配置目录
│   │   ├─convention.php        框架核心偏好配置
│   │   └─···
│   └─Init.php                  框架初始化引导类
│
├─log                           框架日志目录
│   │   
│   ├─client                    客户端日志目录                    
│   │   └─···
│   └─server                    服务端日志目录
│       └─···
│                         
└─public                        框架公共目录
    │
    ├─static                    框架静态文件目录
    │   └─···
    ├─index.php                 第三方框架接入文件
    ├─client.php                客户端接口入口文件
    └─swoole.php                服务端管理入口文件
~~~

## 开发规范
* 类名和类文件名保持一致，以大驼峰方式命名。
* 类命名空间从框架根目录开始命名，且需与路径完全一致（否则第三方接入后，自动引入类文件会冲突失效）。
* 框架初始目录名不可以改变，目录名为小写。
* 常量以全字母大写命名，以下划线'_'分隔，以VSWOOLE开头。
* 其他没有强制要求。