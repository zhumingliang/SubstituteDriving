<?php


namespace app\api\service;


use app\lib\exception\SaveException;
use think\Queue;

class TaskService
{
    public function sendToDriverTask($params)
    {

        $jobHandlerClassName = 'app\api\job\PushOrderToDriver';//负责处理推送订单消息给司机
        $jobQueueName = "pushDriverQueue";//队列名称
        $jobData = [
            'type' => $params['type'],
            'd_id' => $params['d_id'],
            'o_id' => $params['o_id'],
            'company_id' => $params['company_id'],
            'from' => $params['from'],
            'name' => $params['name'],
            'phone' => $params['phone'],
            'start' => $params['start'],
            'end' => $params['end'],
            'distance' => $params['distance'],
            'distance_money' => 0,
            'create_time' => $params['create_time'],
            'limit_time' => $params['limit_time'],
            'p_id' => $params['p_id']
        ];
        $isPushed = Queue::push($jobHandlerClassName, $jobData, $jobQueueName);
       LogService::save("task:begin:".$isPushed);
        //将该任务推送到消息队列
        if ($isPushed == false) {
            LogService::save('启动队列失败');
            throw new SaveException(['msg' => '发送推送给司机失败']);
        }
    }
}