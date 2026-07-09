<?php
/**
 * 
 * OrderAction.class.php (前台购物订单)
 *
 */
if(!defined("ThinkPHP")) exit("Access Denied");
class CartAction extends BaseAction{
	protected   $dao , $sessionid,$cdao;
	function _initialize()
    {
		parent::_initialize();
		$this->dao=M('Cart');
		$this->cdao=M('Attrlist');
		$this->sessionid =  cookie('onlineid');
    }
	public function index()
	{
		$config = F('member.config');

		$cart = $this->dao
			->where("sessionid='".$this->sessionid."'")
			->select();
		$subtotal = 0;
		$total = 0;
		$count = 0;
		foreach($cart as $k=>$r){
			$subtotal += $r['number'] * $r['product_price'];
			$count += $r['number'];
		}
		$total = $subtotal * $config['usep'];
		if($total > $config['onep']){
			$total = $config['onep'];
		}

		$this->assign('cart_count',$count);
		$this->assign(
			'subtotal',
			number_format($subtotal)
		);

		$this->assign(
			'total',
			number_format(floor($total))
		);

		$this->assign('cart',$cart);

		$this->display();
	}
	public function checkout(){
		if($this->Config['isuserbuy'] && empty($this->_userid))$this->error ( L('do_empty'));

		$map['sessionid'] = $this->sessionid;
		$cart = $this->dao->where($map)->select();
		$amount=0;
		foreach($cart as $key=>$r){
			$amount = $amount+$r['price'];
		}
		if(!$cart) $this->error('先に商品を選んでください！カートかごの中に商品がありません!');
		session('ids',$ids);
		$this->assign('amount',$amount);
		$this->assign('cart',$cart);
		$this->assign('buy',1);

		if(isset($_SESSION['discount']) && $_SESSION['discount'] = trim($_POST['coupon'])){
			$discount=$_SESSION['discount'];
		}else{
			$discount=0;
			$_SESSION['discount']=0;
		}
		$this->assign('discount',$discount);
		if(isset($_SESSION['reward']) && $_SESSION['reward'] = trim($_POST['reward'])){
			$reward=$_SESSION['reward'];
		}else{
			$reward=0;
			$_SESSION['reward']=0;
		}

		$this->assign('reward',$reward);

		$total=$amount-$discount-$reward;
		$this->assign('total',$total);
		//地区
		$Area = M('Area')->getField('id,name');
		$shipping = M('Shipping')->where("status=1")->select();
		$payment = M('Payment')->field('id,pay_code,pay_name,pay_fee,pay_fee_type,pay_desc,is_cod,is_online')->where("status=1")->select();
		$address = M('User')->where("id='{$this->_userid}'")->find();
		$this->assign('address',$address);
		$this->assign('payment',$payment);
		$this->assign('Area',$Area);
		$this->assign('shipping',$shipping);
		if($_REQUEST['do']){
			$this->assign('buy',2);
		}
		$this->assign('token',CreateToken());
	    $this->display();
	}
	public function address(){ 
		//获取住所信息
		if($_GET['do']=='new'){ 
			$order = 'id desc';
		}else{ 
			$order = 'isdefault desc';
		}
		$user_address = M('User_address')->where("userid='{$this->_userid}'")->order($order)->select();
		$default_address = $user_address[0];
		$this->assign('default_address',$default_address);
		$this->assign('user_address',$user_address);
		$this->display();
	}
	public function _before_insert(){
		$_POST['ip'] = get_client_ip();
	}
	public function ajax()
	{
		unset($_SESSION['discount']);
		$_SESSION['discount'] = 0;
		$id  = intval($_REQUEST['id']);
		$num = intval($_REQUEST['num']);
		$do  = isset($_REQUEST['do']) ? trim($_REQUEST['do']) : '';
		$res = array(
			'data' => 0,
			'msg'  => ''
		);
		switch ($do) {
			case 'add':
				if ($id <= 0 || $num <= 0) {
					$res['msg'] = '参数错误';
					break;
				}
				$product = M('Product')->find($id);
				if (!$product) {
					$res['msg'] = '商品不存在';
					break;
				}
				$where = array(
					'product_id' => $id,
					'sessionid'  => $this->sessionid
				);
				$cart = $this->dao->where($where)->find();
				if ($cart) {
					$save = array(
						'id'     => $cart['id'],
						'number' => $cart['number'] + $num
					);
					$save['price'] = $save['number'] * $cart['product_price'];
					$rs = $this->dao->save($save);
				} else {
					$price = floatval($product['pro_price']);
					$insert = array(
						'sessionid'      => $this->sessionid,
						'product_id'     => $product['id'],
						'product_thumb'  => $product['thumb'],
						'product_url'    => $product['url'],
						'product_name'   => $product['title'],
						'product_price'  => $price,
						'moduleid'       => 3,
						'number'         => $num,
						'attr'           => '',
						'price'          => $price * $num
					);
					$rs = $this->dao->add($insert);
				}
				$res['data'] = $rs ? 1 : 0;
				break;
			case 'update':
				if ($id <= 0) {
					break;
				}
				if ($num < 1) {
					$rs = $this->dao->delete($id);
					$res['data'] = $rs ? 3 : 0;
					break;
				}
				$cart = $this->dao->find($id);
				if (!$cart) {
					break;
				}
				$save = array(
					'id'     => $id,
					'number' => $num,
					'price'  => $cart['product_price'] * $num
				);
				$rs = $this->dao->save($save);
				$res['data'] = $rs ? 2 : 0;
				break;
			case 'del':
				if ($id <= 0) {
					break;
				}
				$rs = $this->dao->delete($id);
				$res['data'] = $rs ? 3 : 0;
				break;
		}
		exit(json_encode($res));
	}

	//获取数量
	public function getnum(){ 
		$where = 'sessionid='.intval($this->sessionid);
		$num = $this->dao->where($where)->count();
		echo json_encode($num);
	}
	public function clear(){
		$this->dao->where("sessionid = '$this->sessionid' ")->delete();
		$this->success ( L('do_ok'));
	}
	public function done(){
		if($_SESSION['token'] != $_POST['token']){
			$this->error ( L('タイムアウトです。ページを更新してから再送信してください'));
		}
		$model = M('Order');
		$config = F('Config');
		$this->assign('sendmails',$config['sendmails']);
		$this->assign('mails',$config['mail_from']);
		$this->assign('automail',$config['automail']);
		$map = array();
		$map['sessionid'] = $this->sessionid;
		/* 检查购物车中是否有商品 */
		$cart_count = $this->dao->where($map)->count();
		if ($cart_count == 0) $this->error ( L('ORDER_NO_PRODUCT'),U('index'));
		 /* 检查氏名 漢字信息是否完整 */
		 $userid = intval($this->_userid) ;
		 
		 if($_COOKIE['TP_userid']){
			$userid=$_COOKIE['TP_userid'];
			$this->assign('isnew',0);
		 }else{
			$userid=get_userid_by_email(make_semiangle($_POST['email']));
			$pass=M('User')->field('pass')->find($userid);
			autologin($_POST['email'],$pass['pass']);
			$this->assign('pass',$pass['pass']);
			$this->assign('isnew',1);
		 }
		 if($userid=="0"){
			$arr['province']=$_POST['province'];
			$arr['email']=make_semiangle($_POST['email']);
			$arr['consignee1']=$_POST['consignee1'];
			$arr['consignee2']=$_POST['consignee2'];
			$arr['tel1']=make_semiangle($_POST['tel1']);
			$arr['tel2']=make_semiangle($_POST['tel2']);
			$arr['zip']=make_semiangle($_POST['zip1']).make_semiangle($_POST['zip2']);
			$arr['mobile']=make_semiangle($_POST['mobile']);
			$arr['address1']=make_semiangle($_POST['address1']);
			$arr['address2']=make_semiangle($_POST['address2']);
			$arr['address3']=make_semiangle($_POST['address3']);
			$arr['sex']=$_POST['sex'];
			$userid=$this->set_default_user($arr);	
			$this->assign('showmg',1);
			autologin($arr['email'],$arr['zip']);
		}
		if($this->Config['use_address']){
			if($userid){
				$address = M('User_address')->where("userid='$this->_userid' AND isdefault='1' ")->find();
			}else{
				$address = unserialize( cookie('guest_address'));
			}
			if(!$address['province'] || !$address['city'] || !$address['area'] || !$address['address'] || !$address['consignee'] || !$address['mobile']){
				$this->assign('jumpUrl',URL('Home-Order/checkout'));
				$this->error ( L('SHIPPING_ADDRESS_NO_FULL'));
			}
		}else{
			$address_id = intval($_POST['address_id']);
			$address = M('User_address')->find($address_id);
		}
		$order=array();
		/*商品金额*/
		$cart = $this->dao->where($map)->select();
		$amount=0;
		foreach($cart as $key=>$r)	$amount = $amount+$r['price'];
		/*配送方式*/
		$shippingid= intval($_POST['shipping_id']);
		$Shipping = M('Shipping')->find($shippingid);
		/*保价*/
		if(intval($_POST['isinsure'])){ 
			$insure_fee = $amount*$Shipping['insure_fee']/100;
			$insure_fee =  number_format($insure_fee,2);
			if($insure_fee<=$Shipping['insure_low_price']) $insure_fee=$Shipping['insure_low_price']; 
			$order['insure_fee'] = $insure_fee;
		}
		/*支付方式*/
		$paymentid= intval($_POST['payment']);
		$Payment = M('Payment')->find($paymentid);
		/*发票*/
		$order['invoice'] = intval($_POST['invoice']);
		if($order['invoice']){			
			$order['invoice_title']= htmlspecialchars($_POST['invoice_title']);
			$order['invoice_fee'] = $amount*$_POST['invoice_fee']/100;
			$order['invoice_fee'] =  number_format($order['invoice_fee'],2);
		}
		$order['amount'] = $amount;
		$order['shipping_fee'] = number_format($Shipping['first_price'],2);	
		$order['order_amount'] = $order['amount']+$order['invoice_fee']+$order['insure_fee']+$order['shipping_fee'];
		/*发票*/
		if($Payment['pay_fee']){
			$order['pay_fee'] = $Payment['pay_fee_type'] ?  $Payment['pay_fee'] : $order['order_amount']*$Payment['pay_fee']/100;
			$order['pay_fee'] = number_format($order['pay_fee'],2);
		}
		if($_SESSION['reward']==$_POST['reward']){
			$reward=$_POST['reward'];
		}else{
			$reward=0;
		}
		if($_SESSION['discount']==$_POST['discount']){
			$discount=$_POST['discount'];
		}else{
			$discount=0;
		}
		if(isset($_SESSION['bonusn']) && $_SESSION['discount']==$_POST['discount']){
			$coupon=get_coupon_info($_SESSION['bonusn'],$userid);
			if($coupon){
				update_coupon($_SESSION['bonusn'],$coupon['type']);
			}
		}
		$pay_fee=$order['pay_fee']?$order['pay_fee']:0;
		$order['order_amount']=$order['order_amount']+$pay_fee-$discount-$reward;
		$config = F('member.config');
		if($order['order_amount'] && $userid && $config['member_level']==1){
			$order['level_price']=$order['order_amount'];
			$order['order_amount']=total_level_price($order['order_amount'],$userid);
		}
		$order['sex'] =  $_POST['sex'];
		$order['discount'] = $discount;
		$order['reward'] = $reward;
		$order['wap'] = 1;
		$order['userid'] =$userid ;
		$order['status'] = 0;
		$order['pay_status']= 0;
		$order['shipping_status']= 0;
		$order['consignee'] = $_POST['consignee1'].' '.$_POST['consignee2'];
		$order['country'] =  intval($_POST['country']);
		$order['province']  =  intval($_POST['province']);
		$order['city'] =  intval($_POST['city']);
		$order['area'] =  intval($_POST['area']);
		$order['address'] =  make_semiangle($_POST['address1'])." ".make_semiangle($_POST['address2'])." ".make_semiangle($_POST['address3']);
		$order['zipcode'] =  make_semiangle($_POST['zipcode']);
		$order['zip'] =  make_semiangle($_POST['zip1']).make_semiangle($_POST['zip2']);
		$order['tel'] =  make_semiangle($_POST['tel1']).' '.make_semiangle($_POST['tel2']);
		$order['mobile'] =  make_semiangle($_POST['mobile']);
		$order['email'] =  make_semiangle($_POST['email']);
		$order['shipping_id'] =  intval($Shipping['id']);
		$order['shipping_name'] =  $Shipping['name'] ?  $Shipping['name'] : '';
		$order['pay_id'] =  intval($Payment['id']);
		$order['pay_name'] =   $_POST['pay_name'];
		$order['pay_code'] =  $Payment['pay_code'] ? $Payment['pay_code'] : '';
		$order['postmessage'] =  htmlspecialchars($_POST['postmessage']);
		$order['add_time'] =  time();
		$order['posts_time']=time();
		foreach($order as $key=>$r){if($r==null)$order[$key]='';}
		$lists = explode(',', $config['black_list']);
		if(in_array(make_semiangle($_POST['email']),$lists)){
			$order['type'] =  1;
		}
		if($order['email']){
			$orderid= $model->add($order);
		}else{
			$this->error ( L('タイムアウトです。ページを更新してから再送信してください'));
		}
		$_SESSION['order_id'] = $orderid;
		$userid=$userid;
		if($orderid){
			$order['sn'] =get_order_sn($orderid);
			$model->save(array('id'=>$orderid,'sn'=>$order['sn']));
			$arr=array();
			foreach($cart as $key=>$r){
				unset($cart[$key]['id']);
				$infoattr=MD('Attrlist','id='.$r['attrid'],'number');
				$infonumber=$infoattr-$r['number'];
				if($infonumber<0) $infonumber=0;
				M('Attrlist')->where('id='.$r['attrid'])->save(array('number'=>$infonumber));
				M('Product')->where('id='.$cart[$key]['product_id'])->save(array('updatetime'=>time()));
				M('Product')->where('id='.$cart[$key]['product_id'])->setInc('buys',$cart[$key]['number']);

				$prd=M('Product')->field('protype,pinpai')->find($cart[$key]['product_id']);
				$cart[$key]['protype']=$prd['protype'];
				$cart[$key]['pinpai']=$prd['pinpai'];
			
				$cart[$key]['order_id']=$orderid;
				$cart[$key]['userid']=$userid;
				$cart[$key]['sessionid']=$this->sessionid;
				$cart[$key]['add_time']=time();
				M('Order_data')->add($cart[$key]);
				if($userid){
					M('User_collect')->where('userid='.$userid.' and proid='.$cart[$key]['product_id'])->delete();
				}
				//insert_comment($cart[$key]['product_id']);
			}
			M('User')->where('id='.$userid)->setInc('ding',1);
			if($userid && $reward){
				addLogs($reward,$userid,"ポイント割引".$order['sn'],"cut");
			}
			$em=M('Emtpl')->where("id=1")->find();
			if($em){
				$headers = str_replace(array('{consignee}','{sn}'),array($order['consignee'],$order['sn']),$em['title']);
				$messages =$em['description'];
			}

			$array['order_id']=$orderid;
			$array['post_time']=time();
			$array['back_time']=time();
			$array['msg_content']=$messages;
			$array['feedback']='';
			$array['msg_name']=$headers;
			$array['typeid']=1;
			$array['howis']=1;
			$array['userid']=$userid;
			
			M('Qas')->add($array);

			//$this->account_addlog($order['order_amount'],$userid,$order['sn']);
			//sendOrder($order,$arr);
			// var_dump(M('Order_data')->getDberror());
			$this->dao->where($map)->delete();
			if($order['pay_id']){
				if($order['pay_code']=='Balance'){				
					if( $order['order_amount']>0 && $order['order_amount'] <= $user['amount']){
						//减用户余额
						$r =M('User')->where("userid = '$userid'")->setDec('amount',$order['order_amount']);
						if($r){
							$orderup['id'] = $orderid;
							$orderup['status'] = 1;
							$orderup['pay_status'] = 2;
							$orderup['pay_time'] =time();
							$model->save($orderup);
						}else{
							$this->error ( L('do_error'));
						}
					}else{					
						$paybutton='<span><input type="button"  class="button" onclick="window.location.href =\''.URL("User-Pay/Recharge").'\'" value="'.L('Recharge').'" /></span>';
						$this->assign('paybutton',$paybutton);
					}
				}else{
					$pay_code = $order['pay_code'];
					$aliapy_config = unserialize($Payment['pay_config']);
					$aliapy_config['order_sn']= $order['sn'];
					$aliapy_config['order_amount']= $order['order_amount'];
					$aliapy_config['body'] = $order['consignee'].' '.$order['postmessage'];
					import("@.Pay.".$pay_code);
					$pay=new $pay_code($aliapy_config);
					$paybutton = $pay->get_code();
					$this->assign('paybutton',$paybutton);
				}
			}
		}
		$site_payment=$this->Config['site_payment'];
		unset($_SESSION['bonus']);
		unset($_SESSION['coupon']);
		unset($_SESSION['reward']);
		unset($_SESSION['discount']);
		$this->assign('site_payment',htmlspecialchars_decode($site_payment)); 
		$this->assign('order',$order);
		$this->assign('cart',$cart);
		$this->display();
	}
	function ajaxsends(){
		$config = F('Config');
		$order_id =intval($_SESSION['order_id']);
		if($order_id){
			$order = M('Order')->find($order_id);
			$userid=$order['userid'];
			$ispay=M('User')->where('id='.$userid)->field('ispay,black')->find();
		}else{
			exit;
		}
		if($config['automail']==1 && $ispay['ispay']==0 && $ispay['black']==0 ){
			if($order){
				$order_data = M('Order_data')->where("order_id='{$order_id}'")->select();
				$data = M('Area')->find($order['province']);
				$mailto = $order['email'];
				$pro="";
				$orderamount=0;
				if($order_data){
					$i=1;
					$amount=0;
					$subprice=0;
					foreach($order_data as $v){
						$product=M('Product')->field('nypos,sales,ishas')->find($v['product_id']);

			
						if($product['nypos'] == 1 && $product['ishas'] == 0){
							$pro.=$i.'、 '.$v['product_name'].' * '.$v['number'].'つ [売り切れ]<br>';
							$subprice = $subprice+$v['product_price']*$v['number'];
						}else{
							$pro.=$i.'、 '.$v['product_name'].' * '.$v['number'].'つ<br>';
							$amount = $amount+$v['product_price']*$v['number'];
						}
						$i++;
					}
				}
				$total=$amount - $order['discount'] - $order['reward'];
				if($total > 0){
					$orderamount = $total;
				}else{
					$orderamount = 0;
				}
				if($orderamount != $order['order_amount']){
					//M('Order')->where('id='.$order_id)->save(array('order_amount'=>$orderamount));
				}
				if($amount>0){
					if($subprice>0){
						$product = '商品： <br>'.$pro.'
						合計金額：￥ '.round($amount).'円 「品切れ商品の金額を差し引く」<br>
						注文番号：'.$order['sn'].'<br>';
					}else{
						$product = '商品： <br>'.$pro.'
						合計金額：￥ '.round($order['order_amount']).'円<br>
						注文番号：'.$order['sn'].'<br>';
					}
				}else{
					$product = '商品： <br>'.$pro.'
					<br>
					注文番号：'.$order['sn'].'<br>';
				}

				if($amount>0){
					if($order['userid']){
						$usertpl=M('User')->where('status=1 and id='.$order['userid'])->field('tpl')->find();
						if($usertpl['tpl']>0 && $usertpl['tpl'] !=21){
							$temp=M('Emtpl')->where('status=1 and id='.$usertpl['tpl'])->find();
						}
						if(empty($temp)){
							$temp=M('Emtpl')->where('typeid=4 and status=1')->limit(1)->order('rand()')->find();
						}
					}else{
						$temp=M('Emtpl')->where('typeid=4 and status=1')->limit(1)->order('rand()')->find();
					}
				}else{
					$temp=M('Emtpl')->find(21);
				}

				$catid=$temp['id'];
				$title=$temp['title'];
				$tpl=$temp['description'];
				$header = str_replace(array('{consignee}','{sn}'),array($order['consignee'],$order['sn']),$title);
				$message = str_replace(array('{consignee}','{tel}','{zip}','{address}','{mobile}','{product}','{zipcode}','{coupon}','{point}','{amount}','{total}','{sn}'),array($order['consignee'],$order['tel'],$order['zip'],$data['name'] . $order['address'],$order['mobile'],$product,$order['zipcode'],round($order['discount']),round($order['reward']),round($amount),round($orderamount),$order['sn']),$tpl);
				$map['order_id']=trim($order_id);
				$map['post_time']=time();
				$map['msg_content']=trim($message);
				$map['msg_name']=$header;
				$map['feedback']=trim($message);
				$map['back_time']=time();
				$map['typeid']=1;
				$map['howis']=1;
				$map['userid']=$order['userid'];

				if($amount>0 && $temp['status']!=2){
					M('Qas')->add($map);
				}
				if($temp['point']==2 && $subprice>0){
					$reward=$order['reward'];
					if($reward>0){
						M('Order')->where('id='.$order_id)->save(array('reward'=>0));
						addLogs($reward,$order['userid'],$order['sn'].' 売り切れ +'.$reward.'枚','add');
					}
				}
				if($order['userid'] && $temp['mark']==1){
					setUserTpl($order['userid'],$catid);
				}
				if(($temp['status']==1 || $temp['id']==21) && $amount>0){
					$r = sendmail($mailto,$header,$message,$this->Config);
				}
				if($amount>0  && $temp['status']!=2){
					$arr['mail']=$catid;
				}
				$arr['actid']=1;
				$arr['pay_id']=1;
				M('Order')->where("id=".$order_id)->save($arr);
				exit;
			}
		}else{
			exit;
		}
	}
	function callback(){
		$order_id =$_SESSION['order_id'];
		if(!$order_id){
			exit;
		}
		$order = M('Order')->find($order_id);
		$userid=$order['userid'];
		if($userid){
			$userinfo=M('User')->where("id={$userid}")->field('black')->find();
			if($userinfo['black']==1){
				$arrs['type']=1;
				M('Order')->where("id=".$order_id)->save($arrs);
			}
		}
		$zt=http_request_lack(make_semiangle($order['email']));
		if($zt==1){
			$arrs['type']=1;
			M('Order')->where("id=".$order_id)->save($arrs);
		}
		$product=M('Order_data')->field('product_id')->where("order_id={$order_id}")->select();
		if($product){
			foreach($product as $v){
				if($v['product_id']!=41295){
					if(isTopNews($v['product_id'],2000)){
  						comment($v['product_id']);
					}

					break;
				}
			}
			echo 'ok';
		}

	}
	function ajaxsendmail(){
		$config = F('Config');
		$order_id =$_GET['id'];
		if($order_id){
			$order = M('Order')->find($order_id);
			$order_data = M('Order_data')->where("order_id='{$order_id}'")->select();
			$data = M('Area')->find($order['province']);
			$mailto = $order['email'];
			$pro="";
			if($order_data){
				$i=1;
				foreach($order_data as $v){
					$pro.=$i.'、 '.$v['product_name'].' * '.$v['number'].'つ<br>';
					$i++;
				}
			}
			$product = '商品： <br>'.$pro.'
			合計金額：￥ '.round($order['order_amount']).'円（税込）<br>
			注文番号：'.$order['sn'].'<br>';
			$lists = explode(',', $config['black_list']);
			$catid=$_GET['catid'];
			if($catid==1){
				$tpl=$config['mail_tpl'];
			}elseif($catid==2){
				$tpl=$config['mail_tpls'];
			}elseif($catid==4){
				$tpl=$config['mail_san'];
			}else{
				$tpl=$config['mail_mb'];
			}
			$message = str_replace(array('{consignee}','{tel}','{zip}','{address}','{mobile}','{product}','{zipcode}'),array($order['consignee'],$order['tel'],$order['zip'],$data['name'] . $order['address'],$order['mobile'],$product,$order['zipcode']),$tpl);
			if(empty($lists)){
				if($catid==3){
					$r = sendmail($mailto,'お世話様です、'.$order['sn'],$message,$this->Config);
					$map['order_id']=trim($order_id);
					$map['post_time']=time();
					$map['msg_content']='お世話様です、'.$order['sn'];
					$map['msg_name']=trim($order['consignee']);
					$map['feedback']=trim($message);
					$map['back_time']=time();
					$map['typeid']=1;
					M('Qas')->add($map);
				}else{
					$r = sendmail($mailto,'[Bibicopy]注文通知-'.$order['sn'],$message,$this->Config);
					$map['order_id']=trim($order_id);
					$map['post_time']=time();
					$map['msg_content']='[Bibicopy]注文通知-'.$order['sn'];
					$map['msg_name']=trim($order['consignee']);
					$map['feedback']=trim($message);
					$map['back_time']=time();
					$map['typeid']=1;
					M('Qas')->add($map);
				}
				$tagdatas=array();
				if($catid==1){
					$tagdatas['shipping_name']=1;
				}elseif($catid==2){
					$tagdatas['shipping_sn']=1;
				}elseif($catid==4){
					$tagdatas['pay_time']=1;
				}else{
					$tagdatas['pay_id']=1;
				}
				M('Order')->where("id=".$order_id)->save($tagdatas);
			}elseif(!in_array($mailto,$lists)){
				if($catid==3){
					$r = sendmail($mailto,'お世話様です、'.$order['sn'],$message,$this->Config);
					$map['order_id']=trim($order_id);
					$map['post_time']=time();
					$map['msg_content']='お世話様です、'.$order['sn'];
					$map['msg_name']=trim($order['consignee']);
					$map['feedback']=trim($message);
					$map['back_time']=time();
					$map['typeid']=1;
					M('Qas')->add($map);
				}else{
					$r = sendmail($mailto,'[Bibicopy]注文通知-'.$order['sn'],$message,$this->Config);
					$map['order_id']=trim($order_id);
					$map['post_time']=time();
					$map['msg_content']='[Bibicopy]注文通知-'.$order['sn'];
					$map['msg_name']=trim($order['consignee']);
					$map['feedback']=trim($message);
					$map['back_time']=time();
					$map['typeid']=1;
					M('Qas')->add($map);
				}
				$tagdatas=array();
				if($catid==1){
					$tagdatas['shipping_name']=1;
				}elseif($catid==2){
					$tagdatas['shipping_sn']=1;
				}elseif($catid==4){
					$tagdatas['pay_time']=1;
				}else{
					$tagdatas['pay_id']=1;
				}
				M('Order')->where("id=".$order_id)->save($tagdatas);
			}
			if($r==true){
				$this->ajaxReturn($r,L('mailsed_ok'),1);
			}else{
				$this->ajaxReturn(0,L('mailsed_error').$r,1);
			}
			unset($_SESSION['order_id']);
			exit;
		}
	}
	function set_default_user($arr){
		$config = F('member.config');
		$points=$config['points']?$config['points']:0;
		if($arr){
			$data['realname']= $arr['consignee1']." ".$arr['consignee2'];
			$data['username']= $arr['email'];
			$data['email']=$arr['email'];
			$data['province']=$arr['province'];
			$data['consignee1']= $arr['consignee1'];
			$data['consignee2']= $arr['consignee2'];
			$data['tel1']= $arr['tel1'];
			$data['tel2']= $arr['tel2'];
			$data['zip']=$arr['zip'];
			$data['address1']=$arr['address1'];
			$data['address2']=$arr['address2'];
			$data['address3']=$arr['address3'];
			$data['mobile']=$arr['mobile'];
			$data['sex']=$arr['sex'];
			$data['groupid']=3;
			$data['login_count']=0;
			$data['createtime']=time();
			$data['updatetime']=time();
			$data['last_logintime']=time();
			$data['reg_ip']=get_client_ip();
			$data['status']=1;
			$data['amount']=$points;
			$data['pass']=$arr['zip'];
			$data['password'] = sysmd5($arr['zip']);
			M('User')->add($data);
			$uid = M('User')->getLastInsID();
			$ru['role_id'] = 3;
			$ru['user_id']=$uid;
			M('RoleUser')->add($ru);
			$arr['typeid']=1;
			$arr['userid']=$uid;
			$arr['username']=$arr['email'];
			$arr['action']='add';
			$arr['time']=time();
			$arr['note']='新規会員登録クーポン';
			$arr['txt']=$points;
			$arr['ip']=get_client_ip();
			M('Userlog')->add($arr);
			$usersn['usersn']= setUserNo($uid);
			M('User')->where("id=".$uid)->save($usersn);
			return $uid;
		}else{
			return 0;
		}
	}

	/* ============================================================
	 * Apple 风格 - 以下为新增加的方法，不影响原有逻辑
	 * ============================================================ */

	/**
	 * Apple 风格 - 购物车数量更新（Ajax）
	 *  POST id, number
	 */
	public function update(){
		$id  = (int)$_REQUEST['id'];
		$num = max(1, (int)$_REQUEST['number']);
		$map = $this->_cartWhere();
		$map['id'] = $id;
		$row = M('Cart')->where($map)->find();
		if (!$row) $this->ajaxReturn('', 'レコードが存在しません', 0);
		M('Cart')->where('id='.$id)->save(array('number'=>$num));
		$summary = $this->_summaryAjax();
		$this->ajaxReturn($summary, '', 1);
	}

	/**
	 * Apple 风格 - 删除购物车项（Ajax）
	 *  POST id
	 */
	public function remove(){
		$id = (int)$_REQUEST['id'];
		$map = $this->_cartWhere();
		$map['id'] = $id;
		M('Cart')->where($map)->delete();
		$summary = $this->_summaryAjax();
		$summary['empty'] = $summary['count']<=0 ? 1 : 0;
		$this->ajaxReturn($summary, '', 1);
	}

	/**
	 * Apple 风格 - 优惠券/优惠码
	 *  POST code
	 */
	public function coupon(){
		$code = strtoupper(trim((string)$_REQUEST['code']));
		$items = $this->_loadCart();
		$s     = $this->_summaryAjax();
		$discount = 0;
		if ($code === 'KURA10') {
			$discount = round($s['subtotal'] * 0.05);
		} elseif ($code === 'NEW5000') {
			$discount = min(5000, $s['subtotal']);
		} elseif ($code === '') {
			$this->ajaxReturn('', 'クーポンコードを入力してください', 0);
		} else {
			$this->ajaxReturn('', 'このクーポンコードは無効です', 0);
		}
		cookie('cart_coupon', json_encode(array('code'=>$code, 'discount'=>$discount)), 1800);
		$s['discount']      = $discount;
		$s['discount_text'] = number_format($discount).'円';
		$s['total']        -= $discount;
		$s['total_text']    = number_format($s['total']).'円';
		$s['point']         = (int)floor($s['total']/100);
		$this->ajaxReturn($s, $code.' を適用しました', 1);
	}

	/**
	 * Apple 风格 - 模拟支付
	 *  GET ?sn=...
	 */
	public function pay(){
		$uid = (int)$this->_userid;
		$sn  = trim((string)$_REQUEST['sn']);
		$o   = M('Order')->where('sn="'.$sn.'"'.($uid ? ' AND userid='.$uid : ''))->find();
		if (!$o) $this->error('注文が存在しません');
		if ((int)$o['pay_status'] === 1) {
			redirect(U('Cart/donePage', array('sn'=>$sn)));
			exit;
		}
		M('Order')->where('id='.$o['id'])->save(array(
			'status'     => 1,
			'pay_status' => 1,
			'pay_time'   => time(),
		));
		// 加积分
		if ($o['point'] > 0) {
			M('User')->where('id='.$o['userid'])->setInc('point', (int)$o['point']);
		}
		redirect(U('Cart/donePage', array('sn'=>$sn)));
	}

	/**
	 * Apple 风格 - 订单完成页（GET）
	 *  ?sn=...
	 */
	public function donePage(){
		$uid = (int)$this->_userid;
		$sn  = trim((string)$_REQUEST['sn']);
		$o   = M('Order')->where('sn="'.$sn.'"'.($uid ? ' AND userid='.$uid : ''))->find();
		if (!$o) $this->error('注文が存在しません');
		$o['status_label'] = $this->_orderStatus($o);
		$items = M('OrderData')->where('order_id='.$o['id'])->select();
		$this->assign('order', $o);
		$this->assign('items', $items);
		$this->display('Cart:donePage');
	}

	/**
	 * Apple 风格 - 取消订单（Ajax）
	 */
	public function cancel(){
		$uid = (int)$this->_userid;
		$id  = (int)$_REQUEST['id'];
		$o   = M('Order')->where('id='.$id.' AND userid='.$uid)->find();
		if (!$o) $this->ajaxReturn('', '注文が存在しません', 0);
		if ((int)$o['status'] !== 0) $this->ajaxReturn('', 'この注文はキャンセルできません', 0);
		M('Order')->where('id='.$id)->save(array('status'=>4, 'confirm_time'=>time()));
		$this->ajaxReturn('', '注文をキャンセルしました', 1);
	}

	/**
	 * Apple 风格 - 確認受取（Ajax）
	 */
	public function confirm(){
		$uid = (int)$this->_userid;
		$id  = (int)$_REQUEST['id'];
		$o   = M('Order')->where('id='.$id.' AND userid='.$uid)->find();
		if (!$o) $this->ajaxReturn('', '注文が存在しません', 0);
		M('Order')->where('id='.$id)->save(array(
			'status'          => 2,
			'shipping_status' => 2,
			'confirm_time'    => time(),
		));
		$this->ajaxReturn('', '受取を確認しました', 1);
	}

	/* ============================================================
	 * 内部
	 * ============================================================ */

	/**
	 * 购物车 where：未登录按 sessionid，登录按 userid
	 */
	private function _cartWhere(){
		$map = array();
		$uid = (int)$this->_userid;
		if ($uid) {
			$map['userid'] = $uid;
		} else {
			$map['sessionid'] = $this->sessionid;
		}
		return $map;
	}

	/**
	 * 读取购物车
	 */
	private function _loadCart(){
		return M('Cart')->where($this->_cartWhere())->order('id DESC')->select();
	}

	/**
	 * 购物车汇总（用于 Ajax）
	 */
	private function _summaryAjax(){
		$items = $this->_loadCart();
		$subtotal = 0; $count = 0;
		foreach ($items as $it) {
			$subtotal += (float)$it['product_price'] * (int)$it['number'];
			$count    += (int)$it['number'];
		}
		$shippingFee = $subtotal >= 5000 ? 0 : 800;
		$giftFee     = 0;
		$total       = $subtotal + $shippingFee + $giftFee;
		return array(
			'subtotal'      => $subtotal,
			'subtotal_text' => number_format($subtotal).'円',
			'shipping_fee'  => $shippingFee,
			'shipping_text' => $shippingFee ? number_format($shippingFee).'円' : '無料',
			'gift_fee'      => $giftFee,
			'gift_text'     => number_format($giftFee).'円',
			'discount'      => 0,
			'discount_text' => number_format(0).'円',
			'total'         => $total,
			'total_text'    => number_format($total).'円',
			'point'         => (int)floor($total/100),
			'count'         => $count,
		);
	}

	private function _orderStatus($o){
		$status = (int)$o['status'];
		$ship   = (int)$o['shipping_status'];
		$map = array(
			0 => array('text'=>'待付款','cls'=>'unpaid'),
			1 => $ship===1 ? array('text'=>'配送中','cls'=>'ship') : array('text'=>'待发货','cls'=>'paid'),
			2 => array('text'=>'已完成','cls'=>'done'),
			3 => array('text'=>'退款中','cls'=>'refund'),
			4 => array('text'=>'已取消','cls'=>'cancel'),
		);
		return isset($map[$status]) ? $map[$status] : array('text'=>'不明','cls'=>'');
	}
}
?>