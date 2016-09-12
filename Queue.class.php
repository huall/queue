<?php
/**
 * 任务管理类
 * @author Flc <2016-05-23 13:57:37>
 */
namespace Queue;

use Queue\Job;
use Queue\Logger;

class Queue
{
    /**
     * 每次执行完成后，休眠时长(单位：微秒；1秒=1000000微秒)
     * @var int
     */
    protected $usleep = 300000;

    /**
     * 若无任务，闲置休眠时长（单位：微秒；1秒=1000000微秒)
     * @var integer
     */
    protected $idle_usleep = 10000000;

    /**
     * 队列入栈
     * @param  string $classname 类名/类命名空间
     * @param  string $method    类方法
     * @param  array  $params    参数
     * @return array|false            
     */
    public static function push($classname, $method, $params = [])
    {
        return Job::push($classname, $method, $params);
    }

    /**
     * 开始处理队列
     * @return [type] [description]
     */
    public function work()
    {
        while (true) {
            // 默认休眠时间
            $usleep = $this->usleep;

            // 若无任务，则设置为闲置休眠时间
            if (Job::total() == 0) {
                $usleep = $idle_usleep;
            } else {
                $this->workProness();
            }
            
            usleep($usleep); // 休眠
        }
    }

    /**
     * 处理队列
     * @return [type] [description]
     */
    public function workProness()
    {
        Logger::info('----------------队列开始处理------------------');   
        $work = Job::pop();  // 取队列
        Logger::info('队列数据出栈，数据为:' . serialize($work));

        if (!!$work) {
            if (!isset($work['classname']) ||
                !isset($work['method']))
            {
                self::workFail($work['job_id']);
                Logger::alert('队列处理失败，参数不存在(classname|method)');
                exit;
            }

            $class  = new $work['classname'];
            $method = $work['method'];
            $params = unserialize($work['params']);

            //print_r($work);

            try {
                $rs = call_user_func_array([$class, $method], $params);

                if (!$rs || !$rs['status']) {
                    self::workFail($work['job_id']);
                    Logger::alert('队列处理失败，失败原因：' . ($rs['msg'] ?: '未知错误'));
                } else {
                    self::workSuccess($work['job_id']);
                    Logger::info('队列处理成功，返回数据：' . serialize($rs));
                }
                
            } catch (\Exception $ex) {
                self::workFail($work['job_id']);
                Logger::emerg('队列处理失败，处理异常');
            }
        }

        Logger::info('----------------队列处理结束------------------' . PHP_EOL);   
    }

    /**
     * 任务失败
     * @return [type] [description]
     */
    public static function workFail($jobId)
    {
        return Job::fail($jobId);
    }

    /**
     * 任务成功
     * @return [type] [description]
     */
    public static function workSuccess($jobId)
    {
        return Job::success($jobId);
    }

}