<?php
/**
 * 队列任务 - 日志记录
 * @author Flc <2016-05-23 13:57:37>
 */
namespace Queue;

class Logger
{
    // 日志级别 从上到下，由低到高
    const EMERG     = 'EMERG';  // 严重错误: 导致系统崩溃无法使用
    const ALERT     = 'ALERT';  // 警戒性错误: 必须被立即修改的错误
    const CRIT      = 'CRIT';  // 临界值错误: 超过临界值的错误，例如一天24小时，而输入的是25小时这样
    const ERR       = 'ERR';  // 一般错误: 一般性错误
    const WARN      = 'WARN';  // 警告性错误: 需要发出警告的错误
    const NOTICE    = 'NOTICE';  // 通知: 程序可以运行但是还不够完美的错误
    const INFO      = 'INFO';  // 信息: 程序输出信息

    /**
     * 日志单文件最大数据量
     * @var string
     */
    protected static $filesize = '2097152';


    /**
     * INFO类型日志记录
     * @param  string $message 日志内容
     */
    public static function info($message)
    {
        self::save($message, self::INFO);
    }

    /**
     * NOTICE类型日志记录
     * @param  string $message 日志内容
     */
    public static function notice($message)
    {
        self::save($message, self::NOTICE);
    }

    /**
     * WARN类型日志记录
     * @param  string $message 日志内容
     */
    public static function warn($message)
    {
        self::save($message, self::WARN);
    }

    /**
     * ERR类型日志记录
     * @param  string $message 日志内容
     */
    public static function err($message)
    {
        self::save($message, self::ERR);
    }

    /**
     * CRIT类型日志记录
     * @param  string $message 日志内容
     */
    public static function crit($message)
    {
        self::save($message, self::CRIT);
    }

    /**
     * ALERT类型日志记录
     * @param  string $message 日志内容
     */
    public static function alert($message)
    {
        self::save($message, self::ALERT);
    }

    /**
     * EMERG类型日志记录
     * @param  string $message 日志内容
     */
    public static function emerg($message)
    {
        self::save($message, self::EMERG);
    }

    /**
     * 日志记录
     * @param  string $message 日志内容
     * @param  string $type    日志类型
     * @return mixed          
     */
    protected static function save($message, $type = self::INFO)
    {
        $now = date('c');

        $destination = LOG_PATH . 'Queue/' . date('y_m_d').'.log';

        // 自动创建日志目录
        $log_dir = dirname($destination);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }        
        //检测日志文件大小，超过配置大小则备份日志文件重新生成
        if(is_file($destination) && floor(self::$filesize) <= filesize($destination) ){
            rename($destination, dirname($destination) .'/'. time(). '-'. basename($destination));
        }

        // 日志内容
        $log = "[{$now}] [{$type}] {$message}" . PHP_EOL;

        echo $log;

        error_log($log, 3,$destination);
    }
}