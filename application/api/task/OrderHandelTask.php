<?php


namespace app\api\task;


use yunwuxin\cron\Task;

class OrderHandelTask extends Task
{
    public function configure()
    {
        $this->daily(); //设置任务的周期，每天执行一次，更多的方法可以查看源代码，都有注释
    }

    /**
     * 执行任务
     * @return mixed
     */
    protected function execute()
    {
        //...具体的任务执行
    }
}