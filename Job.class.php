<?php
/**
 * 任务管理类
 * @author Flc <2016-05-23 13:57:37>
 */
namespace Queue;

class Job
{
    /**
     * 队列保存名称
     * @var string
     */
    protected static $job_name = 'JobQueue_Lolyo17KYO123';

    /**
     * 队列保存引擎(默认数据库引擎)
     * @var string
     */
    protected static $job_type = 'database';

    /**
     * 队列任务失败最大失败次数
     * @var integer
     */
    protected static $job_fail_max_num = 3;

    /**
     * 分发对象
     * @var null
     */
    protected static $handler = null;

    /**
     * 初始化
     * @return [type] [description]
     */
    public static function init()
    {
        $job_type = ucwords(strtolower(self::$job_type));
        $classname = '\\Queue\\Driver\\' . $job_type;

        self::$handler = new $classname;
    }

    /**
     * 队列入栈
     * @param  string $classname 类名/类命名空间
     * @param  string $method    类方法
     * @param  array  $params    参数
     * @return array|false            
     */
    public static function push($classname, $method, $params = [])
    {
        if (self::$handler == null)
            self::init();

        return self::$handler->push($classname, $method, $params);
    }

    /**
     * 取出队列
     * @return array|false
     */
    public static function pop()
    {
        if (self::$handler == null)
            self::init();

        return self::$handler->pop();
    }

    /**
     * 待处理队列的总数
     * @return int 
     */
    public static function total()
    {
        if (self::$handler == null)
            self::init();

        return self::$handler->total();
    }

    /**
     * 任务处理失败处理
     * @return [type] [description]
     */
    public static function fail($jobId)
    {
        if (self::$handler == null)
            self::init();

        return self::$handler->fail($jobId);
    }

    /**
     * 任务处理成功
     * @param  [type] $jobId [description]
     * @return [type]        [description]
     */
    public static function success($jobId)
    {
        if (self::$handler == null)
            self::init();

        return self::$handler->success($jobId);
    }

    /**
     * 生成jobid
     * @return string 
     */
    public static function getJobId()
    {
        if (function_exists('com_create_guid')) {
            $uuid = com_create_guid();
        } else {
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid   = chr(123)// "{"
                    .substr($charid, 0, 8).$hyphen
                    .substr($charid, 8, 4).$hyphen
                    .substr($charid,12, 4).$hyphen
                    .substr($charid,16, 4).$hyphen
                    .substr($charid,20,12)
                    .chr(125);// "}"
        }
        $uuid = trim($uuid, '{}');
        return $uuid;
    }
}