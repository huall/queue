<?php
/**
 * 队列存储引擎 - 数据库
 * @author Flc <2016-05-23 13:57:37>
 *
 * @表结构
 * CREATE TABLE `ispek_jobs` (
 *    `job_id` varchar(40) NOT NULL COMMENT '任务id',
 *    `classname` varchar(200) NOT NULL DEFAULT '' COMMENT '类名（或命名空间）',
 *    `method` varchar(200) NOT NULL DEFAULT '' COMMENT '方法',
 *    `params` text COMMENT '参数（序列化）',
 *    `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT '状态：0为待处理，1为处理成功，2为处理中，3为处理失败',
 *    `fail_num` int(10) DEFAULT '0' COMMENT '失败次数',
 *    `created_at` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
 *    `updated_at` int(10) NOT NULL DEFAULT '0' COMMENT '更新时间',
 *    PRIMARY KEY (`job_id`),
 *    KEY `status` (`status`),
 *    KEY `created_at` (`created_at`)
 *  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='队列任务';
 */
namespace Queue\Driver;

use Queue\Job;

class Database extends Job
{
    /**
     * 队列入栈
     * @param  string $classname 类名或命名空间
     * @param  string $method    类方法
     * @param  array  $params    参数
     * @return array|false       
     */
    public static function push($classname, $method, $params = [])
    {
        $data = [
            'job_id'     => self::getJobId(),
            'classname'  => $classname,
            'method'     => $method,
            'params'     => serialize($params),
            'status'     => 0,
            'fail_num'   => 0,
            'created_at' => time(),
            'updated_at' => time()
        ];

        if (!M('jobs')->add($data)) {
            return false;
        }

        return $data;
    }

    /**
     * 队列出栈
     * @return array|null
     */
    public static function pop()
    {
        $dbTrans = M();
        $dbTrans->startTrans();

        if (!$data = M('jobs')->where(['status' => 0, 'fail_num' => ['elt', self::$job_fail_max_num]])->order(['created_at' => 'asc'])->limit(1)->lock(true)->find()) {
            $dbTrans->rollback();
            return false;
        }

        // 更新状态为处理中
        $updateData = ['status' => 2, 'updated_at' => time()];
        if (false === M('jobs')->where(['status' => 0, 'job_id' => $data['job_id']])->save($updateData)) {
            $dbTrans->rollback();
            return false;
        }

        $dbTrans->commit();

        return array_merge($data, $updateData);
    }

    /**
     * 待处理队列总数
     * @return int 
     */
    public static function total()
    {
        return M('jobs')->where(['status' => 0, 'fail_num' => ['elt', self::$job_fail_max_num]])->count();
    }

    /**
     * 任务失败更新
     * @param  [type] $jobId [description]
     * @return [type]        [description]
     */
    public static function fail($jobId)
    {
        $job = M('jobs')->where(['job_id' => $jobId])->find();
        if (!$job) {
            return false;
        }

        $fail_num = $job['fail_num'];
        $data = [
            'fail_num'   => $fail_num + 1,
            'updated_at' => time(),
            'status'     => 0,
        ]; 

        if ($fail_num >= self::$job_fail_max_num - 1) {
            $data['status'] = 3;
        } else {
            $data['created_at'] = time(); 
        }

        return false !== M('jobs')->where(['job_id' => $jobId])->save($data);
    }

    /**
     * 队列成功更新
     * @return [type] [description]
     */
    public static function success($jobId)
    {
        $data = [
            'updated_at' => time(),
            'status'     => 1,
        ]; 
        return false !== M('jobs')->where(['job_id' => $jobId])->save($data);
    }
}