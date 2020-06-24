<?php


namespace app\api\job;


use app\api\service\LogService;
use GatewayClient\Gateway;
use think\Exception;
use think\queue\Job;
use zml\tp_aliyun\SendSms;
use zml\tp_tools\Redis;

class PushOrderToDriver
{
    /**
     * fire方法是消息队列默认调用的方法
     * @param Job $job 当前的任务对象
     * @param array|mixed $data 发布任务时自定义的数据
     */
    public function fire(Job $job, $data)
    {
        // 有些消息在到达消费者时,可能已经不再需要执行了
        $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($data);
        if (!$isJobStillNeedToBeDone) {
            //将订单由正在处理集合改为未处理集合
            $job->delete();
            //需改人员信息状态
            return;
        }
        //执行发送推送
        $isJobDone = $this->doJob($data);
        if ($isJobDone) {
            $job->delete();
        } else {
            if ($job->attempts() > 15) {
                $this->updateOrderStatusToNo($data['o_id'], $data['company_id'], $data['d_id']);
                $job->delete();
            } else {
                $job->release(3); //重发任务
            }
        }
    }

    private function updateOrderStatusToNo($order_id, $company_id, $d_id)
    {
        Redis::instance()->sRem('driver_order_receive:' . $company_id, $d_id);
        Redis::instance()->sRem('driver_order_ing:' . $company_id, $d_id);
        Redis::instance()->sAdd('driver_order_no:' . $company_id, $d_id);


        Redis::instance()->sRem('order:ing', $order_id);
        Redis::instance()->sAdd('order:no', $order_id);
    }

    /**
     * 该方法用于接收任务执行失败的通知
     * @param $data  string|array|... 发布任务时传递的数据
     */
    public function failed($data)
    {
        //可以发送邮件给相应的负责人员
        $this->updateOrderStatusToNo($data['o_id'], $data['company_id'], $data['d_id']);
        LogService::save("失败:" . json_encode($data));

//        print("Warning: Job failed after max retries. job data is :".var_export($data,true)."\n");
    }

    /**
     * 有些消息在到达消费者时,可能已经不再需要执行了
     * @param array|mixed $data 发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function checkDatabaseToSeeIfJobNeedToBeDone($data)
    {
        $set = "webSocketReceiveCode";
        $code = $data['p_id'];
        $check = Redis::instance()->sIsMember($set, $code);
        LogService::save('check:'.$check);
        return $check;
    }

    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doJob($data)
    {
        try {
            LogService::save(\GuzzleHttp\json_encode($data));
            $push_data = [
                'type' => $data['order'],
                'order_info' => [
                    'o_id' => $data['o_id'],
                    'from' => $data['from'],
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'start' => $data['start'],
                    'end' => $data['end'],
                    'distance' => $data['distance'],
                    'create_time' => $data['create_time'],
                    'p_id' => $data['p_id']

                ]
            ];
            $d_id = $data['d_id'];
            LogService::save(self::prefixMessage($push_data));
            Gateway::sendToUid('driver' . '-' . $d_id, self::prefixMessage($push_data));
            return false;
        } catch (Exception $e) {
            LogService::save('error:'.$e->getMessage());

            return false;
        }
    }

    private
    function prefixMessage($message)
    {
        $data = [
            'errorCode' => 0,
            'msg' => 'success',
            'type' => $message['type'],
            'data' => $message['order_info']

        ];
        return json_encode($data);

    }
}