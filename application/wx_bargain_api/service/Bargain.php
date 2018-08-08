<?php
namespace app\wx_bargain_api\service;
use think\Exception;
use app\wx_bargain_api\model\Data as DataModel;
use app\wx_bargain_api\model\Goods as GoodsModel;
use app\wx_bargain_api\model\Helpers as HelpersModel;
use app\lib\exception\BargainException;

// 砍价算法
class Bargain{
    public function returnMoney($pNum,$orderId,$goods_id){

        
        // $pNum =1020;
        $index = $pNum % 50;
        $datas=NULL;
        $money = NULL;//获取砍价金额

        switch ($goods_id) {
            case 1:
                $datas = DataModel::where('goods_id',1)->find();
                
                break;
            case 2:
                $datas = DataModel::where('goods_id',2)->find();
                
                break;
            default:
                $datas=NULL;
                break;
        }
        // return $pNum;
        
        if((int)$pNum<=50){
            $str = $datas->p50;
            // return count(explode(',',$str));
            $money = explode(',',$str)[$index];
        }else if((int)$pNum>50 && (int)$pNum <= 100){
            $str = $datas->p100;
            // return count(explode(',',$str));
            $money = explode(',',$str)[$index];
        }else if((int)$pNum>100 && (int)$pNum <= 200){
            $str = $datas->p200;
            if($str){
                // return count(explode(',',$str));
                $money = explode(',',$str)[$index];
            }else{
                // 金属自锁矫正100人后
                $money = NULL;
            }
        }else {
            // 隐适美200人之后
            $money = NULL;
        }

        // 到了最后砍价阶段
        if($money == NULL){

            // 当前订单所砍掉的总价
            $nowTotalBargainMoney = HelpersModel::where('order_id',$orderId)->sum('bargain_money');

            

            $goods = GoodsModel::get($goods_id);
            // 原价
            $oldPrice = $goods->original_price;


            // 当前实时价格(成交价)) = 原价 - 当前订单所砍掉的总价
            $nowPrice = $oldPrice - $nowTotalBargainMoney;
            

            // 还可以继续砍价的金额 = 当前实时价格 - 底价
            $lowPrice = $goods->activity_money;
            $lastAbleMoney = $nowPrice - $lowPrice;

            // 这个阶段的金额按照规则只能砍0.5-1元
            if($lastAbleMoney>0){
                $money = randomFloat(0.5,1);
            }else{
                throw new BargainException([
                    'msg' => '当前已经是该产品最底价了，别再砍了!',
                    'code' => 403,
                    'errorCode' => 60004
                ]);
            }

            
        }


        return $money;
    }
}