<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
class Shopcart extends Controller
{
    public function index()
    {
        return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:) </h1><p> ThinkPHP V5.1<br/><span style="font-size:30px">12载初心不改（2006-2018） - 你值得信赖的PHP框架</span></p></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=64890268" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="eab4b9f840753f8e7"></think>';
    }   

    //清空购物车
    public function clearShopCart()
    {
        //从session中获取userid
        $userid = 2;
        $result = Db::name('cart')
        ->where('userid',$userid)
        ->delete();
        //返回删除的行数
        return $result;
    }

    //根据商品id从购物车移除商品
    public function removeById()
    {
        //从session中获取userid
        $userid = 1;
        
        $cartid = $_GET['cartid'];
        $result = Db::name('cart')
        ->where('cartid',$cartid)
        ->delete();

        if($result){
            //查询商品总价
            $total = Db::table('shop_cart')
            ->alias('c')
            ->join('shop_goodsinfo g','c.goodsid = g.goodsid')
            ->where('c.userid ='.$userid)
            ->field('sum(c.num*g.price) as total')
            ->select();

            $result = json(['total'=>$total[0]['total']]);
        }
        
        return $result;
    }

    //修改商品数量
    public function modifyNumber()
    {
        //接受用户id
        $userid = 1;
        //接受购物车的cartid
        $cartid = $_GET['cartid'];
        //获取加入购物车的商品数量
        $num = $_GET['num'];

        // return $cartid.$num;

        //检查cartid是否在购物车表中存在
        $result = Db::name('cart')
        ->where('cartid',$cartid)
        ->update(['num'=>$num]);

        //查询商品价格
        $price = Db::name('cart')
        ->alias('c')
        ->join('goodsinfo g','c.goodsid=g.goodsid')
        ->where('c.cartid',$cartid)
        ->field('g.price')
        ->find();

                //计算用户购物总价
        $total = Db::table('shop_cart')
        ->alias('c')
        ->join('shop_goodsinfo g','c.goodsid = g.goodsid')
        ->where('c.userid ='.$userid)
        ->field('sum(c.num*g.price) as total')
        ->select();

        if($result)
        {
            // return true;
            return json(['num'=>$num,'cartid'=>$cartid,'price'=>$price['price'],'total'=>$total[0]['total']]);
        }else{
            return false;
        }

    }

    //添加商品到购物车
    public function addToCart()
    {
        //接受购物车的cartid
        $cartid = $_GET['cartid'];
        //获取商品id
        $goodsid = $_GET['goodsid'];
        //获取加入购物车的商品数量
        $num = $_GET['num'];
        //获取用户id
        $userid = 1;

        //查询shop_cart表中是否已存在商品
        $result = Db::name('cart')
        ->where('cartid',$cartid)
        ->find();

        //如果存在则数量num加1，不存在则执行插入一条记录
        if($result)
        {
            $r = Db::name('cart')
            ->where('cartid',$cartid)
            ->setInc('num',$num);
        }else{
            $data = ['num'=>$num,'goodsid'=>$goodsid,'userid'=>$userid];

            $r = Db::name('cart')->insert($data);
        }
        //$r 获取执行插入或更新的结果
        if($r==1){
            return true;
        }else{
            return false;
        }
    }

    //显示购物车内容
    public function showCart()
    {
        //获取当前用户id
        $userid = 1;

        //根据用户id查询shop_cart和shop_goodsinfo表
        $data = Db::table('shop_cart')
        ->alias('c')
        ->join('shop_goodsinfo g','c.goodsid = g.goodsid')
        ->where('c.userid ='.$userid)
        ->field('cartid,thumb,goods_name,price,num,price*num subtotal')
        ->select();

        //计算用户购物总价
        $total = Db::table('shop_cart')
        ->alias('c')
        ->join('shop_goodsinfo g','c.goodsid = g.goodsid')
        ->where('c.userid ='.$userid)
        ->field('sum(c.num*g.price) as total')
        ->select();

        return $this->fetch('buycar',['data'=>$data,'total'=>$total[0]['total']]);
    }

    //统计商品数量进入订单确认页面
    public function account()
    {
        //获取当前用户id
        $userid = 1;

        //根据用户id查询shop_cart和shop_goodsinfo表
        $data = Db::table('shop_cart')
        ->alias('c')
        ->join('shop_goodsinfo g','c.goodsid = g.goodsid')
        ->where('c.userid ='.$userid)
        ->field('cartid,thumb,goods_name,num,num*price as subtotal')       
        ->select();

        //计算用户购物总价
        $total = Db::table('shop_cart')
        ->alias('c')
        ->join('shop_goodsinfo g','c.goodsid = g.goodsid')
        ->where('c.userid ='.$userid)
        ->field('sum(c.num*g.price) as total')
        ->select();

        //获取地址信息
        $addr = Db::name('addr')
        ->alias('a')
        ->join('user u','a.userid =u.userid')
        ->where('a.userid',$userid)
        ->order('a.ctime desc')
        ->limit(1)
        ->find();
        // print_r($data);
        // print_r($total);
        print_r($addr);

        //视图赋值
        $this->assign('addr',$addr);
        return $this->fetch('buycar_two',['data'=>$data,'total'=>$total[0]['total']]);
    }
}