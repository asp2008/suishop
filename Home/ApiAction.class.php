<?php
/**
 * 
 * IndexAction.class.php(后台首页)
 *
 */
if(!defined("ThinkPHP")) exit("Access Denied");
class ApiAction extends BaseAction
{
	protected   $cache_model;
	function _initialize()
    {
		parent::_initialize();
    }
    public function send(){

        $this->mission();
        $plan = null;
        $foundData = true;
        while($foundData){
            $plan=M('Market')->where('status=1')->order('hits asc,id asc')->find();
            $order_sn_count = M('Email_sendlist')->where("status=1 and plan_id = ".$plan['id'])->count();
            if($order_sn_count == 0){
                M('Market')->where('id='.$plan['id'])->save(array('status'=>0));
            }
            if ($order_sn_count > 0) {
                $foundData = true;
                break;
            } else {
                $foundData = false;
                break;
            }
        }
        if(!$foundData){
            $arr['status']=2;
            echo "没有可发的邮件队列 ";
            exit;
        }
        if($plan){
            M('Market')->where('id='.$plan['id'])->setInc('hits',1);
            $time=time();
            $tasks = M('Email_sendlist')->where("status=1 and plan_id = ".$plan['id'].' and last_send <= '.$time.'')->order('last_send asc,id asc,error asc')->find();
            //print_r(M('Email_sendlist')->getLastSql());
        }
        if($plan && $tasks){
            if($plan['mail_server']){
                $config['mail_server']=$plan['mail_server'];
                $config['mail_port']=$plan['mail_port'];
                $config['mail_from']=$plan['mail_user'];
                $config['mail_user']=$plan['mail_user'];
                $config['mail_password']=$plan['mail_password'];
                $config['suffix']=$plan['suffix'];
            }else{
                $config=array();
            }

            $tasks['catid']=$plan['tpl'];

            if($plan['typeid']==1){
                $this->newOrder($tasks,$config);
            }
            if($plan['typeid']==2){
                $this->customer($tasks,$config);
            }
            if($plan['typeid']==3){
                $this->subscribe($tasks,$config);
            }
            if($plan['typeid']==4){
                $this->sendCoupon($tasks,$config);
            }
            if($plan['typeid']==5){
                $this->subscribe($tasks,$config);
            }
            if($plan['typeid']==6){
                $this->subscribe($tasks,$config);
            }
            if($plan['typeid']==7){
                $this->sendCoupon($tasks,$config);
            }

            $arr['status']=1;
            if($plan['title']){
                echo $plan['title'];
            }else{
                echo "本次发送无标题 ";
            }
            
            exit;
        }else{
            $arr['status']=2;
            echo "本次轮空 ";
            exit;
        }
    }
    public function customer($data,$config){
        if(!empty($data)){
            $catid=$data['catid'];
            $temp=M('Emtpl')->where("id=".$catid)->find($catid);
            $mailto=$data['email'];
            $title=$temp['title'].' ['.date("Y.m.d",time()).']';
            $content=$temp['description'];
            $header =$title;
            $message = $content;
            try
            {
                if (!empty($config['suffix']) && substr($mailto, -strlen($config['suffix'])) === $config['suffix']) {
                    $arr['error']=1;
                    $arr['text']="暂停发该类型";
                    $arr['status']=0;
                    M('Email_sendlist')->where("id=".$data['id'])->save($arr);
                    exit;
                }
                $r = sendmail($mailto,$header,$message,$config);
                if($r){
                    $arr['error']=1;
                    $arr['status']=2;
                    $arr['text']=$r;
                    M('Email_sendlist')->where("id=".$data['id'])->save($arr);
                }else{
                    $arr['error']=1;
                    $arr['text']=$r;
                    $arr['status']=0;
                    M('Email_sendlist')->where("id=".$data['id'])->save($arr);
                }
            }catch(Exception $e){
                $arr['error']=1;
                $arr['status']=0;
                $arr['text']=$e->getMessage();
                M('Email_sendlist')->where("id=".$data['id'])->save($arr);
            }
            return true;
        }else{
            return false;
        }
    }
    public function subscribe($data,$config){
        if(!empty($data)){
            $catid=$data['catid'];
            $temp=M('Emtpl')->where("id=".$catid)->find($catid);
            $mailto=$data['email'];
            $title=$temp['title'].' ['.date("Y.m.d",time()).']';
            $content=$temp['description'];
            $header =$title;
            $message = $content;
            try
            {
                if (!empty($config['suffix']) && substr($mailto, -strlen($config['suffix'])) === $config['suffix']) {
                    $arr['error']=1;
                    $arr['text']="暂停发该类型";
                    $arr['status']=0;
                    M('Email_sendlist')->where("id=".$data['id'])->save($arr);
                    exit;
                }
                $r = sendmail($mailto,$header,$message,$config);
                if($r){
                    $arr['error']=1;
                    $arr['status']=2;
                    $arr['text']=$r;
                    M('Email_sendlist')->where("id=".$data['id'])->save($arr);
                }else{
                    $arr['error']=1;
                    $arr['text']=$r;
                    $arr['status']=0;
                    M('Email_sendlist')->where("id=".$data['id'])->save($arr);
                }
            }catch(Exception $e){
                $arr['error']=1;
                $arr['status']=0;
                $arr['text']=$e->getMessage();
                M('Email_sendlist')->where("id=".$data['id'])->save($arr);
            }
            return true;
        }else{
            return false;
        }
    }
    public function sendCoupon($data,$config){
        if(!empty($data)){
            $catid=$data['catid'];
            $temp=M('Emtpl')->where("id=".$catid)->find($catid);
            $mailto=$data['email'];
            $title=$temp['title'].' ['.date("Y.m.d",time()).']';
            $content=$temp['description'];
            $header =$title;
            if($data['userid']){
                $code=sendCtouser($data['userid'],40);
                $info=M('Coupon')->where("id=40")->find();
                $deadline=date('m/d/Y H:i:s',strtotime("+7 days"));
                $money=$info['money'];
                $message = str_replace(array('{code}','{deadline}','{money}'),array($code,$deadline,$money),$content);
            }else{
                $message = $content;
            }
            try
            {
                if (!empty($config['suffix']) && substr($mailto, -strlen($config['suffix'])) === $config['suffix']) {
                    $arr['error']=1;
                    $arr['text']="暂停发该类型";
                    $arr['status']=0;
                    M('Email_sendlist')->where("id=".$data['id'])->save($arr);
                    exit;
                }
                $r = sendmail($mailto,$header,$message,$config);
                if($r){
                    $arr['error']=1;
                    $arr['status']=2;
                    $arr['text']=$r;
                    M('Email_sendlist')->where("id=".$data['id'])->save($arr);
                }else{
                    $arr['error']=1;
                    $arr['text']=$r;
                    $arr['status']=0;
                    M('Email_sendlist')->where("id=".$data['id'])->save($arr);
                }
            }catch(Exception $e){
                $arr['error']=1;
                $arr['status']=0;
                $arr['text']=$e->getMessage();
                M('Email_sendlist')->where("id=".$data['id'])->save($arr);
            }
            return true;
        }else{
            return false;
        }
    }
    public function newOrder($data,$config){
        $order_id=$data['order_id'];
        $catid=$data['catid'];
        if($order_id){
            $order = M('Order')->find($order_id);
            $order_data = M('Order_data')->where("order_id='{$order_id}'")->select();
            $Area = M('Area')->find($order['province']);
            $mailto = $data['email'];
            $pro="";
            $orderamount=0;
            $subprice=0;
            if($order_data){
                $i=1;
                $amount=0;
                foreach($order_data as $v){
                    $product=M('Product')->field('nypos,sales,pro_price')->find($v['product_id']);
                    if($product['nypos'] == 1){
                        $pro.=$i.'、 '.$v['product_name'].' * '.$v['number'].'つ [売り切れ]<br>';
                        $subprice = $subprice+$product['pro_price']*$v['number'];
                    }else{
                        $pro.=$i.'、 '.$v['product_name'].' * '.$v['number'].'つ<br>';
                        $amount = $amount+$product['pro_price']*$v['number'];
                    }
                    $i++;
                }
            }
            if($subprice>0){
                $orderamount = $amount;
            }else{
                $orderamount = $order['order_amount'];
            }
            $product = '商品： <br>'.$pro;
            $temp=M('Emtpl')->find($catid);
            $title=$temp['title'].' ['.date("Y.m.d",time()).']';
            $tpl=$temp['description'];
            $header = str_replace(array('{consignee}','{sn}'),array($order['consignee'],$order['sn']),$title);
            $message = str_replace(array('{consignee}','{tel}','{zip}','{address}','{mobile}','{product}','{zipcode}','{coupon}','{point}','{amount}','{total}','{sn}'),array($order['consignee'],$order['tel'],$order['zip'],$Area['name'] . $order['address'],$order['mobile'],$product,$order['zipcode'],round($order['discount']),round($order['reward']),round($amount),round($orderamount),$order['sn']),$tpl);
            try
            {
                if (!empty($config['suffix']) && substr($mailto, -strlen($config['suffix'])) === $config['suffix']) {
                    $arr['error']=1;
                    $arr['text']="暂停发该类型";
                    $arr['status']=0;
                    M('Email_sendlist')->where("id=".$data['id'])->save($arr);
                    exit;
                }
                $r = sendmail($mailto,$header,$message,$config);
                if($r){
                    $arr['error']=1;
                    $arr['status']=2;
                    $arr['text']=$r;
                    M('Email_sendlist')->where("id=".$data['id'])->save($arr);
                }else{
                    $arr['error']=1;
                    $arr['text']=$r;
                    $arr['status']=0;
                    M('Email_sendlist')->where("id=".$data['id'])->save($arr);
                }
            }catch(Exception $e){
                $arr['error']=1;
                $arr['status']=0;
                $arr['text']=$e->getMessage();
                M('Email_sendlist')->where("id=".$data['id'])->save($arr);
            }
            $map['order_id']=trim($order_id);
            $map['post_time']=time();
            $map['msg_content']=trim($message);
            $map['msg_name']=$header;
            $map['feedback']=trim($message);
            $map['back_time']=time();
            $map['typeid']=1;
            $map['howis']=1;
            $map['userid']=$order['userid'];
            M('Qas')->add($map);
            return true;
        }
    }
    public function pushmark(){
        if(empty($_REQUEST)){
            $this->error (L('do_empty'));
        }
        $order_email_arr=$_REQUEST['products'];
        if($order_email_arr){
            $order_id_arr=explode(',',$order_email_arr);
        }else{
            $order_id_arr=$_REQUEST['ids'];
        }
        $plan_id=$_REQUEST['market'];
        $typeid=$_REQUEST['typeid']?$_REQUEST['typeid']:3;
        $data=array();
        foreach($order_id_arr as $k => $v){
            $info=M('Email_sendlist')->where('email="'.$v.'" and plan_id='.$plan_id)->count();
            if($info==0){
                $data[$k]['email']=$v;
                $data[$k]['userid']=0;
                $data[$k]['last_send']=randomDate();
                $data[$k]['plan_id']=$plan_id;
                $data[$k]['typeid']=$typeid;
                $data[$k]['order_id']=0;
            }
        }
        if($data){
            M('Email_sendlist')->addAll($data);
            $this->success('推送成功');
        }else{
            $this->error ("数据已经存在");
        }
    }
    public function pushcoupon(){
		$model = M ( 'Email_sendlist' );
		$status=$_REQUEST['status'];
        $ids=$_POST['ids'];
		if(!empty($ids) && is_array($ids)){
			$id=implode(',',$ids);
			$data = $model->select($id);
			if($data){				
				foreach($data as $key=>$r){	
					$model->save(array('id'=>$r['id'],'status'=>$status));	
				}
				$this->success(L('do_ok'));
			}else{
				$this->error(L('do_error').': '.$model->getDbError());
			}
		}else{
			$this->error(L('do_empty'));
		}
    }
    public function delall(){
        $model = M ( 'Email_sendlist' );
		$ids=$_POST['ids'];
		if(!empty($ids) && is_array($ids)){
			$id=implode(',',$ids);
			if(false!==$model->delete($id)){
				$this->success(L('delete_ok'));
			}else{
				$this->error(L('delete_error').': '.$model->getDbError());
			}
		}else{
			$this->error(L('do_empty'));
		}
    }
    public function play(){
        $model = M ('Email_sendlist');
        $plan_id=$_REQUEST['id'];
        M('Market')->where('id='.$plan_id)->save(array('startime'=>time()));
        $temp=M('Market')->find($plan_id);
        $startime=$temp['startime'];
        $endtime=$temp['endtime'];
        $list=$model->where("status !=2 and plan_id=".$plan_id)->field('id')->select();
        if(($endtime>$startime) && $list){
            $total_seconds =  $endtime - $startime;
            $seconds_per_person = $total_seconds / count($list);
            $i=1;
            foreach($list as $k=>$v){
                $startimes=$startime + $i * $seconds_per_person;
                $arr['last_send']=$startimes;
                $model->where("id=".$v['id'])->save($arr);
                $i++;
            }
            $this->success("设置成功");
        }else{
            $this->error(L('do_empty'));
        }
    }
    public function mission(){
        $today = date("Y-m-d");
        $endTimeOfDay = strtotime($today . " 21:00:00");
        $mission= M('Market')->where("status=1")->count();
        if(time()>$endTimeOfDay || $mission==0){
            M('Config')->where('varname="send_mail_on"')->save(array('value'=>0));
            savecache('Config');
        }
    }
    public function start(){
        $model = M ('Email_sendlist');
        $config = F('member.config');
        $model->where("status=2")->delete();
        $minutes =$config['send_Interval'] * 60;
        $startime=time();       
        $list=$model->where("status=1 and typeid !=3")->field('id,plan_id')->select();
        if(count($list)>0){
            $i=1;
            $p1=1;
            $p3=1;
            $p4=1;
            $p5=1;
            $p6=1;
            $p7=1;
            $p8=1;
            $p9=1;
           // shuffle($list);
            foreach($list as $k=>$v){
                $startimes=$startime + $i * $minutes+rand(0,count($list));
                if($v['plan_id']==1){
                    $p1++;
                }
                if($v['plan_id']==3){
                    $p3++;
                }
                if($v['plan_id']==4){
                    $p4++;
                }
                if($v['plan_id']==5){
                    $p5++;
                }
                if($v['plan_id']==6){
                    $p6++;
                }
                if($v['plan_id']==7){
                    $p7++;
                }
                if($v['plan_id']==8){
                    $p8++;
                }
                if($v['plan_id']==9){
                    $p9++;
                }
                $arr['last_send']=$startimes;
                $model->where("id=".$v['id'])->save($arr);
                $i++;
            }
            M('Market')->where('id=1')->save(array('startime'=>time(),'endtime'=>$startime + $p1 * $minutes,'status'=>1));
            M('Market')->where('id=3')->save(array('startime'=>time(),'endtime'=>$startime + $p3 * $minutes,'status'=>1));
            M('Market')->where('id=4')->save(array('startime'=>time(),'endtime'=>$startime + $p4 * $minutes,'status'=>1));
            M('Market')->where('id=5')->save(array('startime'=>time(),'endtime'=>$startime + $p5 * $minutes,'status'=>1));
            M('Market')->where('id=6')->save(array('startime'=>time(),'endtime'=>$startime + $p6 * $minutes,'status'=>1));
            M('Market')->where('id=7')->save(array('startime'=>time(),'endtime'=>$startime + $p7 * $minutes,'status'=>1));
            M('Market')->where('id=8')->save(array('startime'=>time(),'endtime'=>$startime + $p8 * $minutes,'status'=>1));
            M('Market')->where('id=9')->save(array('startime'=>time(),'endtime'=>$startime + $p9 * $minutes,'status'=>1));
            M('Market')->where('userid=1')->save(array('hits'=>1));
            echo json_encode($arr['status']=1);
            exit;
        }else{
            echo json_encode($arr['status']=0);
            exit;
        }
    }
    public function done(){
        $status=$_REQUEST['status'];
        M('Config')->where('varname="send_mail_on"')->save(array('value'=>$status));
        savecache('Config');
        if($status==1){
            $this->start();
        }
        echo json_encode($arr['status']=1);
        exit;
    }
}
?>
