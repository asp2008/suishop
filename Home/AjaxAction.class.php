<?php
/**
 * 
 * AreaAction.class.php (ajax 获取住所)
 *
 */
if(!defined("ThinkPHP")) exit("Access Denied");
class AjaxAction extends BaseAction
{
    public function index()
    {
	 exit;
    }
    public function area()
    {
		$module = M('Area');
		$id = intval($_REQUEST['id']);
		$level= intval($_REQUEST['level']);
		$provinceid= intval($_REQUEST['provinceid']);
		$cityid= intval($_REQUEST['cityid']);
		$areaid= intval($_REQUEST['areaid']);
		$province_str='<option value="0">選択してください</option>';
		$city_str='<option value="0">请选择城市...</option>';
		$area_str='<option value="0">请选择区域...</option>';
		$str ='';
		$r = $module->where("parentid=".$id)->select();	 		
		foreach($r as $key=>$pro){
			$selected = ( $pro['id']==$provinceid) ? ' selected="selected" ' : '';
			$str .='<option value="'.$pro['id'].'"'.$selected.'>'.$pro['name'].'</option>';
		}
		if($level==0){
			$province_str .=$str;
		}elseif($level==1){
			$city_str .=$str;
		}elseif($level==2){
			$area_str .=$str;
		}
		$str='';
		if($provinceid){
			$rr = $module->where("parentid=".$provinceid)->select();	 		
			foreach($rr as $key=>$pro){
				$selected = ($pro['id']==$cityid) ? ' selected="selected" ' : '';
				$str .='<option value="'.$pro['id'].'"'.$selected.'>'.$pro['name'].'</option>';
			}
			$city_str .=$str;
		}
		$str='';
		if($cityid){
			$rrr = $module->where("parentid=".$cityid)->select();	 		
			foreach($rrr as $key=>$pro){
				$selected = ($pro['id']==$areaid) ? ' selected="selected" ' : '';
				$str .='<option value="'.$pro['id'].'"'.$selected.'>'.$pro['name'].'</option>';
			}
			$area_str .=$str;
		}
		$res=array();
		$res['data']= $rs ? 1 : 0 ;
		$res['province'] =$province_str;
		$res['city'] =$city_str;
		$res['area'] =$area_str;
		echo json_encode($res); exit;
	 exit;
    }
	public function address(){
		$do=get_safe_replace($_REQUEST['do']);
		$model = M('User_address');
		$id = intval($_REQUEST['id']);
		$provinceid= intval($_REQUEST['province']);
		$cityid= intval($_REQUEST['city']);
		$areaid= intval($_REQUEST['area']);
		$userid = $_POST['userid'] = $this->_userid;
		if($do=='save'){
			$id= intval($_POST['id']);
			$_POST['isdefault']=1;
			if($userid){				
				$model->where("userid=".$userid)->save(array('isdefault'=>0));				
				if($id){
					$r = $model->save($_POST);
					if($model->getDbError())die(json_encode(array('id'=>0)));
					$_POST['edit'] =1;				
				}else{
					$where['province'] = array('eq',$provinceid);
					$where['city'] = array('eq',$cityid);
					$where['area'] = array('eq',$areaid);
					$where['consignee'] = array('eq',$_POST['consignee']);
					$where['address'] = array('eq',$_POST['address']);
					$ir = $model->where($where)->find();
					if($ir){
						echo json_encode(array('error'=>'收货信息已经存在！'));exit;
					}
					$id=$model->add ($_POST);
				}
			}else{
					$_POST['id']=1;
					$data = serialize($_POST);
					cookie('guest_address',$data,315360000);
					$id=1;
					$_POST['edit'] =1;
			}
			if($id){
				$_POST['id'] =$id;
				die(json_encode($_POST));
			}else{
				die(json_encode(array('id'=>0)));
			}
		}elseif($do=='get'){
			if($userid){	
				$data=$model->find($id);
			}else{
				$data = unserialize( cookie('guest_address'));
			}
			if($data){
				die(json_encode($data));
			}else{
				die(json_encode(array('id'=>0)));
			}
			exit;
		}
	}
	public function shipping(){
		$do=get_safe_replace($_REQUEST['do']);
		$model = M('Shipping');
		$id = intval($_REQUEST['id']); 
		if($do=='get'){
			$data=$model->find($id);
			if($data){
				echo json_encode($data);
			}else{
				echo json_encode(array('id'=>0));
			}
			exit;
		}
	}
	public function sms(){
		$config = $this->Config;
		$mobile = get_safe_replace($_REQUEST['mobile']);
		$smscode = mt_rand('100000','999999');
		$xlh = time().mt_rand(0,100);
		$msg = '尊敬的用户，您的临时验证码为：'.$smscode.'。请填入该验证码！';
		$msg = iconv('UTF-8', 'GB2312',$msg);
		$url = 'http://116.255.134.71/api/SMS.aspx?Command=Send';
		$url .= '&Username='.$config['sms_user'];
		$url .= '&Password='.$config['sms_pass'];
		$url .= '&Mos='.$mobile; // 接收電話番号
		$url .= '&Msg='.$msg;	// 内容
		$url .= '&seqID='.$xlh; // 序列号
		$xml = file_get_contents($url);
		$xml = simplexml_load_string($xml);
		$data = json_decode(json_encode($xml),TRUE);
		if($data['Result']==0){
			session('smscode',$smscode);
		}
		echo json_encode($data);
		exit;
	}
	public function dianjis(){
		$table = M('Bbs');
		$id = $_POST['id'];
		$type = $_POST['type'];
		$cookiename = $id."_".$type;
		$typeold = $table->where("id='$id'")->find();
		$supportold = $typeold[$type];
		$data[$type]=$supportold+1;
		if(cookie($cookiename)){
			echo "0";
		}else{
			$update = $table->where("id='$id'")->save($data);
			$typenew = $table->where("id='$id'")->find();
			cookie($cookiename,1,3600*24);
			echo $typenew[$type];
		}
	}
	public function collect(){
		$proid = $_POST['pid'];
		$userids = $_POST['userid'];
		$collectTable = M('shoucang');
		$thumb = M('user')->where("id='$userids'")->find();
		$chaxunrs = $collectTable->where("userid = '{$userids}' and pid = '$proid'")->count();
		if($chaxunrs>0){
			echo "yiyou";
		}else{
			$_POST['thumb']=$thumb['avatar'];
			$_POST['createtime']=time();
			$collectTable->create();
			$collectTable->add();
			$counts = $collectTable->where("pid = '$proid'")->count();
			echo $counts;
		}
	}
	public function zan(){
		$moduleid = intval($_GET['moduleid']);
		$id = intval($_GET['id']);
		$name = 'zan_'.$moduleid;
		$ids = cookie($name);
		$ids =  explode(',', $ids);
		if(!in_array($id,$ids)){
			$ids[] = $id;
			cookie($name,implode(',', $ids));
			$rs = M($this->module[$moduleid]['name'])->where('id='.$id)->setInc('zan');
			$rs = $rs?1:0;
		}else{
			$rs = 2;
		}
		echo json_encode($rs);
	}
	public function shoucang(){
		$map = array();
		$map['moduleid'] = $moduleid = intval($_GET['moduleid']);
		$map['pid'] = $id = intval($_GET['id']);
		$m = M('Shoucang');
		if(!$m->where($map)->count()){
			$vo = M($this->module[$moduleid]['name'])->field('content',true)->find($id);
			$data = $map;
			$data['createtime'] = $data['updatetime'] = time();
			$data['status'] = 1;
			$data['title'] = $vo['title'];
			$data['thumb'] = $vo['thumb'];
			$data['url'] = $vo['url'];
			$data['userid'] = $this->_userid;
			$data['username'] = $this->_username;
			 $rs = $m->add($data);
			 $rs = $rs?1:0;
		}else{
			$rs = 2;
		}
		echo json_encode($rs);
	}
	public function remen(){
		//初始化
		$array = search_name();
		$limit = 4;
		$sort = 'createtime';
		$desc = 'desc';
		$field = 'createtime,id,catid';
		$map = $data = array();
		//查询条件
		if($posid = intval($_GET['posid'])) $map['posid'] = $posid;
		if($_GET['sort']) $field = $_GET['sort'].','.$field;
		//缓存
		$file_name = $field.$posid;
		$list = S($file_name);
		if(!$list){
	        foreach ($array as $k => $name) {
	            $row = M($name)->where($map)->field($field)->order($sort.' '.$desc)->limit($limit)->select();
	            if($row) $data = array_merge($data,$row);
	        }
	        //重新排序
	        if($desc == 'desc'){
	            arsort($data);
	        }else{
	            asort($data);
	        }
	        $i=0;
	        foreach ($data as $k => $v) {
	            $i++;
	            if($i>$limit) break;
	            $name = $this->categorys[$v['catid']]['module'];
	            $row = M($name)->find(intval($v['id']));
	            $row['catname'] = $this->categorys[$v['catid']]['catname'];
	            $row['module'] = $name;
	            $list[] = $row; 
	        }
	        S($file_name,$list,86400);
        }
        $this->assign('list',$list);
        $this->display();
	}
	/* 
	* 调用快递100 返回数据
	* message(状态),nu(快递号),ischeck(0),com(快递公司),updatetime(更新时间),
	*  status(状态码),condition(00),data(详细列表),state(0)
	*/
	function express(){ 
		$sn = $_GET['sn'];
		import("@.ORG.Express");
		$express = new Express();
 		$result  = $express -> getorder($sn);
 		echo json_encode($result);
	}
 	public function smtypesl(){
 		$keyid=$_POST['keyid'];
 		$list=M('Protype')->where('keyid = '.$keyid.' and parentid > 0')->field('id,parentid,name,keyid')->select();
 		if($list){
 			$this->ajaxReturn($list,'ok',1);exit;
 		}
 	}
 	public function smtypeprols(){
 		$keyid=$_POST['keyid'];
 		$data['list']=M('Protype')->where('keyid = '.$keyid.' and parentid > 0')->field('id,parentid,name,keyid')->select();
 		$data['prols']=M('Product')->where('status = 1 and bigtype = '.$keyid)->field('id,title')->select();
 		if($data){
 			$this->ajaxReturn($data,'ok',1);exit;
 		}
 	}
 	public function typrprols(){
 		$type=$_POST['type'];
 		$list=M('Product')->where('status = 1 and smalltype = '.$type)->field('id,title')->select();
 		$this->ajaxReturn($list,'ok',1);exit;
	}
	 function ems(){
		$filename = $_FILES['file']['tmp_name'];
		if (empty ($filename)) {
			echo '请选择要导入的CSV文件！';
		} else {
			$handle = fopen($filename, 'r');
			$result = $this->input_csv($handle);
			$len_result = count($result);
			if ($len_result == 0) {
				echo '没有任何数据！';
			} else {
				for ($i = 1; $i < $len_result; $i++) {
					$title = $result[$i][0];
					$wurl =$result[$i][1];
					$keywords = $result[$i][2];
					$description = $result[$i][3];
					$time=strtotime($result[$i][4]);
					$url='/sagawas-'.$wurl.'.html';
					$product_id=$result[$i][5];
					$data_values .= "('$title','$url','$wurl','$keywords','$description','29','1','admin','0','$time','$time','$product_id'),";
				}
				$data_values = substr($data_values,0,-1);
				fclose($handle);
				$sql = "insert into div_sagawas(title,url,wurl,keywords,description,catid,userid,username,status,createtime,updatetime,product_id) values".$data_values;
				$reply=M();
				$reply->query($sql);
			}
			exit(json_encode(array("code"=>0,"msg"=>"导入成功！","file"=>$filename,"size"=>$filename),0));
		}	  
	 }
	 function input_csv($handle) {
		setlocale(LC_ALL, 'zh_CN');
        $out = array ();
        $n = 0;
        while ($data = fgetcsv($handle, 10000)) {
            $num = count($data);
            for ($i = 0; $i < $num; $i++) {
				$val = mb_convert_encoding($data[$i], "UTF-8", "GBK");
                $out[$n][$i] = $val;
            }
            $n++;
        }
        return $out;
	}
	function comment(){
		$id=$_POST['id'];
		$mo=$_POST['mo'];
		if($mo){
			api_comment($id,$mo);
		}else{
			insert_comment($id,$mo);
		}
		echo "评论添加成功！";
	}
	function edit(){
		$model =M('Product');	
		$data['id']= $_POST['id'];
		if($_POST['type']=='wurl'){
			$where='wurl ="'.$_POST['thisvalue'].'"';
			$count=$model->where($where)->count();
			if($count==0){
				$data['url']= 'product-'.$_POST['thisvalue'].'.html';
	    		$data[$_POST['type']]= $_POST['thisvalue'];
	    	}
		}else{
			$data[$_POST['type']]= $_POST['thisvalue'];
		}
		$model->save($data);
	}
	function edits(){
		$model =M('Reply');	
		$data['id']= $_POST['id'];
		if($_POST['type']=='createtime'){
			$data[$_POST['type']]= strtotime($_POST['thisvalue']);
		}else{
			$data[$_POST['type']]= trim($_POST['thisvalue']);
		}
		$model->save($data);
	}
	function prices(){
		$model =M('Protype');	
		$data['id']= $_POST['id'];
		$data[$_POST['type']]= trim($_POST['thisvalue']);
		$model->save($data);
	}
	function words(){
		$title=trim($_GET['key']);
		$list=M('Words')->where('status = 1')->field('id,diytitle,skey')->select();
		$arr=array();
		foreach($list as $k=>$v){
			$arrs=explode(',',$v['skey']);
			if(is_array($arrs)){
				foreach($arrs as $t=>$j){
					if(strstr($title,$j)){
						$arr[$t]['id']=$v['id'];
						$arr[$t]['title']=$v['diytitle'];
					}
				}
			}
		}
		echo json_encode($arr);
	}
	function savewords(){
		$key=trim($_GET['key']);
		$where='diytitle ="'.$key.'"';
		$count=M('Words')->where($where)->field('id,diytitle')->find();
		if(!$count){
			$data['createtime'] = $data['updatetime'] = time();
			$data['status'] = 1;
			$data['catid']  = 35;
			$data['title'] = $key;
			$data['diytitle'] = $key;
			$data['content'] = $key;
			$data['userid'] = 1;
			$data['username'] = 'admin';
			$rs = M('Words')->add($data);
			M('Words')->where('id='.$rs)->save(array('url'=>'/words-'.$rs.'.html'));
			$arr=array();
			$arr['id']=$rs;
			$arr['title']=$key;
			echo json_encode($arr);
		}else{
			$arr=array();
			$arr['id']=$count['id'];
			$arr['title']=$count['diytitle'];
			echo json_encode($arr);
		}
	}
	function addTags(){
		$msg=0;
		$tag_id=$_GET['id'];
		$ids=explode(',',$_GET['key']);
		if($ids){
			foreach($ids as $r){
				$info=M('Product')->where('id='.$r)->field('words')->find();
				if(strstr($info['words'],$tag_id)){
				   $arr=$tag_id;
				   M('Product')->where('id='.$r)->save(array('words'=>$arr));
				   $msg=1;
				}else{
					$arr=$info['words'].','.$tag_id;
				    M('Product')->where('id='.$r)->save(array('words'=>$arr));
					$msg=2;
				}
			}
		}
		echo $msg;
	}
	function deltag(){
		$id=$_GET['id'];
		$tag_id=$_GET['tag'];
		$info = M('Product')->where('id='.$id)->field('words')->find();
		$arr=explode(',',$info['words']);
		foreach( $arr as $k=>$v) {
			if($tag_id == $v) unset($arr[$k]);
		}
		$arr=implode(',',$arr);
		M('Product')->where('id='.$id)->save(array('words'=>$arr));
		echo 2;
	}
	function subscribes(){
		$email=trim($_POST['email']);
		$where='openid ="'.$email.'"';
		$count=M('User_sdk')->where($where)->field('id,openid')->find();
		if(!$count){
			$data['uid'] = 1;
			$data['type']  = 2;
			$data['openid'] = $email;
			$data['addtime'] = time();
			$rs = M('User_sdk')->add($data);
			echo 1;
		}else{
			echo 2;
		}
	}

	function addLink(){
		$tt=trim($_GET['tt']);
		$ll=trim($_GET['ll']);
		$data['keyword'] =$tt;
		$data['links']  = $ll;
		$data['date'] = time();
		$rs = M('Search')->add($data);
		echo 1;
		exit;
	}

	function bonus(){

		$id=get_safe_replace($_GET['sn']);
		$userid=cookie('userid')?cookie('userid'):0;
		$data=get_coupon_info($id,$userid);
	
		$this->daos=M('Cart');
		$this->sessionid =  cookie('onlineid');
		$map['sessionid'] =$this->sessionid;
		$cart = $this->daos->where($map)->select();
		$amount=0;
		foreach($cart as $r)	$amount = $amount+$r['price'];

		if($amount<$data['conditions']){
			unset($_SESSION['discount']);
			$_SESSION['discount'] =0;
			unset($_SESSION['bonusn']);
			$_SESSION['bonusn'] =0;
			echo 0;
			exit;
		}
		unset($_SESSION['discount']);
		$_SESSION['discount'] = $data['money'];
		unset($_SESSION['bonusn']);
		$_SESSION['bonusn'] = $id;
		$arr = array(
			'code'=>1,
			'price'=>$data['money'], 
			'total'=>$amount-$data['money'], 
		);
		echo json_encode($arr);
		exit;
	}

	public function coupon(){
		$code   = get_safe_replace($_GET['code']);
		$userid = intval(cookie('userid'));
		$coupon = get_coupon_info($code, $userid);
		if (!$coupon) {
			$this->ajaxReturn(['code' => 0, 'msg' => '优惠券无效'], 'JSON');
		}

		$sessionid = cookie('onlineid');
		$cart = M('Cart')->where(['sessionid' => $sessionid])->select();

		$total = 0;
		foreach ($cart as $item) {
			$total += $item['number'] * $item['product_price'];
		}
		if ($total < $coupon['conditions']) {
			$_SESSION['discount'] = 0;
			$_SESSION['bonusn']   = 0;

			$this->ajaxReturn([
				'code' => 0,
				'msg'  => '未满足优惠券使用条件'
			], 'JSON');
		}

		$reward = isset($_SESSION['reward']) ? intval($_SESSION['reward']) : 0;
		$discount = min(intval($coupon['money']), max(0, $total - $reward));

		$_SESSION['discount'] = $discount;
		$_SESSION['bonusn']   = $code;

		$payTotal = max(0, $total - $reward - $discount);

		$this->ajaxReturn([
			'code'     => 1,
			'coupon'    => $discount,
			'reward'   => $reward,
			'total'    => $payTotal,
		], 'JSON');
	}


	public function reward(){
		$reward = max(0, intval($_GET['reward']));
		$config = F('member.config');

		if (!$this->_userid) {
			$this->ajaxReturn(['status' => 1, 'msg' => '未登录'], 'JSON');
		}

		$user = M('User')->where(['id' => $this->_userid])->find();
		if (!$user) {
			$this->ajaxReturn(['status' => 2, 'msg' => '用户不存在'], 'JSON');
		}

		if ($user['amount'] < $reward) {
			$this->ajaxReturn(['status' => 2, 'msg' => '积分不足'], 'JSON');
		}
		$sessionid = cookie('onlineid');
		$cart = M('Cart')->where(['sessionid' => $sessionid])->select();
		$total = 0;
		foreach ($cart as $item) {
			$total += $item['number'] * $item['product_price'];
		}
		$maxByOrder = $total * floatval($config['usep']);
		if ($maxByOrder < $reward) {
			$this->ajaxReturn(['status' => 3, 'msg' => '超出订单可用积分'], 'JSON');
		}
		$reward = min($reward, intval($config['onep']));
		$reward = min($reward, $user['amount'], $maxByOrder);
		$_SESSION['reward'] = $reward;
		$coupon   = isset($_SESSION['coupon'])   ? intval($_SESSION['coupon'])   : 0;
		$discount = isset($_SESSION['discount']) ? intval($_SESSION['discount']) : 0;
		$payTotal = max(0, $total - $coupon - $discount - $reward);
		$this->ajaxReturn([
			'code'     => 0,
			'total'    => '¥'.number_format($payTotal),
			'reward'   => $reward,
			'coupon'   => $coupon,
			'discount' => $discount
		], 'JSON');
	}


	function dels(){
		$id=$_POST['id'];
		M('User_sdk')->where("id=".$id)->setInc('uid');
	}
	function sale(){
		$order = D('Order');
        $sql = "select left(FROM_UNIXTIME(add_time),7) as date, count(1) as count from div_order group by left(FROM_UNIXTIME(add_time),7) order by left(FROM_UNIXTIME(add_time),7) desc limit 12";
        $result = $order->query($sql);
		//print_r($result);
        $this->ajaxReturn(array('code'=>200,'result'=>array_reverse($result)));
	}
	function day(){
		$order = D('Order');
		$today_start=mktime(0,0,0,date('m'),date('d'),date('Y'));
		$today_end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
		$month_start=mktime(0,0,0,date('m'),1,date('Y'));
		$month_end=mktime(23,59,59,date('m'),date('d')+1,date('Y'));
		$sql = 'SELECT DATE_FORMAT(FROM_UNIXTIME(`add_time`),"%d") AS day,COUNT(*) AS count FROM div_order WHERE `add_time` BETWEEN '.$month_start.' AND '.$month_end. '  GROUP BY day ORDER BY day DESC ';
        $result = $order->query($sql);
		//print_r($result);
        $this->ajaxReturn(array('code'=>200,'result'=>array_reverse($result)));
	}
	function respond(){
		$key=$_POST['token'];
		$sn=trim($_POST['sn']);
		$safe=trim($_POST['safe']);
	
		if( md5($safe) != $_SESSION['verify']){
			echo 1;
			exit;
		}else{
			$arr = M('Order')->where('sn="'.$sn.'" or email="'.$sn.'" or mobile="'.$sn.'"')->field('id')->order('id desc')->find();
			if($arr){
				setcookie('safe', rand(1111,9999), time() + 60*60); 
				setcookie('sn_id',$arr['id'], time() + 60*60);
				setcookie('uemail', $arr['email'], time() + 60*60);
				echo 2;
				exit;
			}
			if (filter_var($sn, FILTER_VALIDATE_EMAIL)) {
				setcookie('uemail', $sn, time() + 60*60);
				echo 2;
				exit;
			}else{
				echo 1;
				exit;
			}
		}
		exit;
	}
	function send(){
		$data['order_id']=trim($_POST['sn_id']);
		$data['post_time']=time();
		$data['msg_content']=trim($_POST['message']);
		$data['msg_name']=trim($_POST['msg_name']);
		$msgid= M('Qas')->add($data);
		if($msgid){
			return 2;
			exit;
		}else{
			return 1;
			exit;
		}
		exit;
	}
	
	function feedback(){
		$data=array();
		$data['back_time']=time();
		$msg_id= $_POST['id'];
		$data['feedback']=trim($_POST['thisvalue']);
		M('Qas')->where("msg_id=".$msg_id)->save($data);
	}
	function makeToken(){
		if(isset($_COOKIE['token'])){
			echo $_COOKIE['token'];
		}else{
			$token = md5(sha1(substr(time(),3,7)));
			setcookie('token', $token, time() + 60*60);
			echo $token;
		}
	}
	function checkOrderNum(){
		if (empty($_SESSION['last_check']))
		{
			$_SESSION['last_check'] = time();
			echo 0;
		}
		$time=$_SESSION['last_check'];
		$count =D('Order')->where('add_time >= '.$time)->count();
		//print_r(D('Order')->getlastsql());
		if($count>=1){
			echo 1;
		}
	}
	function checkSpeedOrderNum(){
		if (empty($_SESSION['last_checks']))
		{
			$_SESSION['last_checks'] = time();
			echo 0;
		}
		$time=$_SESSION['last_checks'];
		$count =D('Qas')->where('post_time >= '.$time)->count();
		//print_r(D('Qas')->getlastsql());
		if($count>=1){
			echo 1;
		}
	}

	function upload() {
		$save_path = './Uploads/';
		$save_url = '/Uploads/';
		$ext_arr = array(
			'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
		);
		$max_size = 1000000;
		$save_path = realpath($save_path) . '/';
		if (!empty($_FILES['imgFile']['error'])) {
			switch($_FILES['imgFile']['error']){
				case '1':
					$error = '超过php.ini允许的大小。';
					break;
				case '2':
					$error = '超过表单允许的大小。';
					break;
				case '3':
					$error = '图片只有部分被上传。';
					break;
				case '4':
					$error = '请选择图片。';
					break;
				case '6':
					$error = '找不到临时目录。';
					break;
				case '7':
					$error = '写文件到硬盘出错。';
					break;
				case '8':
					$error = 'File upload stopped by extension。';
					break;
				case '999':
				default:
					$error = '未知错误。';
			}
			alert($error);
		}
		if (empty($_FILES) === false) {
			$file_name = $_FILES['imgFile']['name'];
			$tmp_name = $_FILES['imgFile']['tmp_name'];
			$file_size = $_FILES['imgFile']['size'];
			if (!$file_name) {
				alert("请选择文件。");
			}
			if (@is_dir($save_path) === false) {
				alert("上传目录不存在。");
			}
			if (@is_writable($save_path) === false) {
				alert("上传目录没有写权限。");
			}
			if (@is_uploaded_file($tmp_name) === false) {
				alert("上传失败。");
			}
			if ($file_size > $max_size) {
				alert("上传文件大小超过限制。");
			}
			$dir_name = empty($_GET['dir']) ? 'image' : trim($_GET['dir']);
			if (empty($ext_arr[$dir_name])) {
				alert("目录名不正确。");
			}
			$temp_arr = explode(".", $file_name);
			$file_ext = array_pop($temp_arr);
			$file_ext = trim($file_ext);
			$file_ext = strtolower($file_ext);
			if (in_array($file_ext, $ext_arr[$dir_name]) === false) {
				alert("上传文件扩展名是不允许的扩展名。\n只允许" . implode(",", $ext_arr[$dir_name]) . "格式。");
			}
			if ($dir_name !== '') {
				$save_path .= $dir_name . "/";
				$save_url .= $dir_name . "/";
				if (!file_exists($save_path)) {
					mkdir($save_path);
				}
			}
			$ymd = date("Ymd");
			$save_path .= $ymd . "/";
			$save_url .= $ymd . "/";
			if (!file_exists($save_path)) {
				mkdir($save_path);
			}
			$new_file_name = date("YmdHis") . '_' . rand(10000, 99999) . '.' . $file_ext;
			$file_path = $save_path . $new_file_name;



			if (move_uploaded_file($tmp_name, $file_path) === false) {
				alert("上传文件失败。");
			}
			//@chmod($file_path, 0644);
			$file_url = $save_url . $new_file_name;	


			//$file_url=thumbs('.'.$file_url,800,800);
		//	$file_url=thumbs($file_url,800,800);		
			echo json_encode(array('error' => 0, 'url' => $file_url));
			exit;
		}		
	}


	function delfeed(){
		$id=$_POST['id'];
		M('Qas')->where("msg_id=".$id)->delete();
		echo "1";
		exit;
	}
	function googles(){
		$filename = $_FILES['file']['tmp_name'];
		if (empty ($filename)) {
			echo '请选择要导入的CSV文件！';
		} else {
			$handle = fopen($filename, 'r');
			$result = $this->input_csv($handle);
			$len_result = count($result);
			if ($len_result == 0) {
				echo '没有任何数据！';
			} else {
				for ($i = 1; $i < $len_result; $i++) {
					$urls=parse_url($result[$i][0]);
					$url1=$urls['path'];
					if(strpos($url1,'pro') !== false){
						$url = $url1;
						$date = time();
						$hit=$result[$i][1];
						$shows=$result[$i][2];
						$clicks=$result[$i][3];
						$pm=$result[$i][4];
						$data_values .= "('$url','$date','$hit','$shows','$clicks','$pm'),";
					}
				}
				$data_values = substr($data_values,0,-1);
				fclose($handle);
				$sql = "insert into div_keys(url,date,hit,shows,clicks,pm) values".$data_values;
				$reply=M();
				$reply->query($sql);
			}
			exit(json_encode(array("code"=>0,"msg"=>"导入成功！","file"=>$filename,"size"=>$filename),0));
		}
	 }
	 function kword(){
		$filename = $_FILES['file']['tmp_name'];
		if (empty ($filename)) {
			echo '请选择要导入的CSV文件！';
		} else {
			$handle = fopen($filename, 'r');
			$result = $this->input_csv($handle);

			$len_result = count($result);
			if ($len_result == 0) {
				echo '没有任何数据！';
			} else {
				for ($i = 1; $i < $len_result; $i++) {
					$keyword=addslashes(trim($result[$i][0]));
					$links = $result[$i][1];
					$date = time();
					$data_values .= "('$keyword','$links','$date'),";
				}
				$data_values = substr($data_values,0,-1);
			
				fclose($handle);
				$sql = "insert into div_search(keyword,links,date) values".$data_values;
				$reply=M();
				$reply->query($sql);

			}
			exit(json_encode(array("code"=>0,"msg"=>"导入成功！","file"=>$filename,"size"=>$filename),0));
		}
	 }
	 function gather(){
		$filename = $_FILES['file']['tmp_name'];
		if (empty ($filename)) {
			echo '请选择要导入的CSV文件！';
		} else {
			$handle = fopen($filename, 'r');
			$result = $this->input_csv($handle);
			$len_result = count($result);
			if ($len_result == 0) {
				echo '没有任何数据！';
			} else {
				for ($i = 1; $i < $len_result; $i++) {
					if($result[$i][0]){
						$title=$result[$i][0];
						$target=$result[$i][1];
						$typeid=$result[$i][2];
						$data_values .= "('$target','$title','$typeid'),";
					}
					
				}
				$data_values = substr($data_values,0,-1);
				fclose($handle);
				$sql = "insert into div_gather(title,target,typeid) values".$data_values;
				$reply=M();
				$reply->query($sql);
			}
			exit(json_encode(array("code"=>0,"msg"=>"导入成功！","file"=>$filename,"size"=>$filename),0));
		}
	 }
	 function transfer(){
		$filename = $_FILES['file']['tmp_name'];
		if (empty ($filename)) {
			echo '请选择要导入的CSV文件！';
		} else {
			$handle = fopen($filename, 'r');
			$result = $this->input_csv($handle);
			$len_result = count($result);
			if ($len_result == 0) {
				echo '没有任何数据！';
			} else {
				for ($i = 1; $i < $len_result; $i++) {
					if($result[$i][0]){
						$title=$result[$i][0];
						$data_values .= "('$title',1),";
					}
					
				}
				$data_values = substr($data_values,0,-1);
				fclose($handle);
				$sql = "insert into div_transfer(title,status) values".$data_values;
				$reply=M();
				$reply->query($sql);
			}
			exit(json_encode(array("code"=>0,"msg"=>"导入成功！","file"=>$filename,"size"=>$filename),0));
		}
	 }

	 function jsons(){
		header("Content-Type: application/javascript");
		echo "var fx='本物を真似た偽物模造品複製品です。 パチ物 （バチ物パチモノぱちもの）バッタ物（バッタモン）ブランドコピーとかの言い方もありますが、これらは一般的に粗悪品のレプリカ商品をさしているようです。 その中でも非常に優れた商品をハイレプ ルイヴィトン（ハイグレードレプリカの略）若しくは、スーパーレプリカルイヴィトン、スーパーコピールイヴィトンなどと言います。 アジア圏内の中国大陸韓国上海台湾などの工場で作られる。中でも中国の広州で作られるレプリカは非常に優れたものです。■当店が数年、超スーパーコピーN級品の販売経験を持っております。 製造技術がかなり成熟したルイヴィトン、シャネル、エルメス、グッチ、ロレックス 、オメガ、ウブロ、フランクミュラー、カルティエ、パネライ、オーデマピゲ、パテックフィリップのコピー品、時計、バッグ、財布、靴、小物、手帳、アクセサリー、ベルトなど種類が揃っています。 仕入、検品、梱包など全部一流のベテランがしております。 商品とともに、高品質と安心をお届けいたします！'";
	 }

	function getparents(){
		$parentid=intval($_REQUEST ['parentid']);
		$sctpes=M('protype')->where('parentid = '.$parentid)->field('id,name,listorder,keyid,catdir,status,uprice,dprice,stock,iscomment')->order('listorder asc')->select();

		$html='';
		foreach($sctpes as $r){
			if($r['status']==0){
				$aa='<a href="?g=admin&amp;m=protype&amp;a=status&amp;id='.$r['id'].'&amp;status=1"><font color="red">禁用</font></a>';
			}else{
				$aa='<a href="?g=admin&amp;m=protype&amp;a=status&amp;id='.$r['id'].'&amp;status=0"><font color="green">启用</font></a>';
			}
			if($r['stock']==1){
				$stock='<a href="?g=admin&amp;m=protype&amp;a=stock&amp;id='.$r['id'].'&amp;stock=0"><font color="red">下架404</font></a>';
			}else{
				$stock='<a href="?g=admin&amp;m=protype&amp;a=stock&amp;id='.$r['id'].'&amp;stock=1"><font color="green">上架</font></a>';
			}


			if($r['iscomment']==2){
				$iscomment='<a href="?g=admin&amp;m=protype&amp;a=status&amp;id='.$r['id'].'&amp;iscomment=1"><font color="red">否</font></a>';
			}else{
				$iscomment='<a href="?g=admin&amp;m=protype&amp;a=status&amp;id='.$r['id'].'&amp;iscomment=2"><font color="green">是</font></a>';
			}

			$uprice=$r['uprice']?$r['uprice']:0;
			$dprice=$r['dprice']?$r['dprice']:0;
			$html.='<tr id="cat_'.$r['id'].'" class="son_'.$parentid.' sons">
			<td width="90" align="center" style="padding-left:20px">&nbsp;&nbsp;└&nbsp;&nbsp;<input name="listorders['.$r['id'].']" type="text" size="3" value="'.$r['listorder'].'"></td>
			<td align="center">'.$r['id'].'</td>
			<td><a href="/'.$r['catdir'].'/" target="_blank">'.$r['name'].'</a></td>
			<td></td>
			<td align="center"><a href="javascript:;" data-id="'.$r['id'].'" class="addproduct">添加</a>  | 
			<a href="javascript:;" class="products" data-id="'.$r['id'].'">列表</a></td>
			<td class="edit" data-id="'.$r['id'].'" data-type="uprice">'.$uprice.'</td>
			<td class="edit" data-id="'.$r['id'].'" data-type="dprice">'.$dprice.'</td>
			<td align="center">'.$aa.'</td>
			<td align="center">'.$stock.'</td>
			<td align="center">'.$iscomment.'</td>
			<td align="center">
			<a href="javascript:;" data-id="'.$r['id'].'" class="show_tag">展开</a> |
			<a href="javascript:;" class="lists"  data-id="'.$r['id'].'">标签</a> | 
			<a href="?g=admin&amp;m=protype&amp;a=index&amp;keyid='.$r['keyid'].'&amp;parentid='.$r['id'].'">管理分类</a> |
			<a href="?g=admin&amp;m=protype&amp;a=add&amp;keyid='.$r['keyid'].'&amp;parentid='.$r['id'].'">添加子分类</a> |
			<a href="?g=admin&amp;m=protype&amp;a=edit&amp;keyid='.$r['keyid'].'&amp;id='.$r['id'].'">修改</a> |
			<a href="javascript:confirm_delete(\'?g=admin&amp;m=protype&amp;a=delete&amp;id='.$r['id'].'\')">削除</a>
			</td>      		
			</tr>';
		}
		echo $html;
	}

	function down(){
		$config = F('Config');
		$id=$_GET['aid'];
		$arr=M('Deposit')->find($id);

		$html=$arr['addr'].$arr['pro'].$arr['arrt'].$arr['content'];

		$subject=$arr['title'];
		$from=$config['site_email'];
		$to=$arr['email'];
		$time=$arr['createtime'];

		$fnak=iconv("utf-8","gb2312", $subject);
		$url="./Uploads/email/".$fnak.".eml";
		preparehtmlmail($to, $from, $subject, $html,$time);
		echo  $url;
		exit;
	}
	function showemail(){
		$id=$_GET['aid'];
		$info= M('Deposit')->find($id);
		$info['createtime']=date("Y-m-d H:i:s",$info['createtime']);
		$sn=$info['ordersn'];
		$data=  M('Order')->where("sn='$sn'")->field('id,consignee,tel,zip,mobile,address,zipcode,email,province,area,userid')->find();

		$Area = M('Area')->getField('id,name');

		$info['consignee']=$data['consignee'];
		$info['tel']=$data['tel'];
		$info['zip']=$data['zip'];
		$info['mobile']=$data['mobile'];
		$info['address']=$Area[$data['province']].' '.$Area[$data['area']].' '.$data['address'];
		$info['zipcodes']=$data['zipcode'];
		$info['sn']=$sn;
		$info['userid']=$data['userid'];
		$info['orderid']=$data['id'];
		if($data['id']){
			$msg=getMsgs($data['id']);
			$info['message']=$msg;
			$info['product']=getProducts($data['id']);
		}else{
			$info['message']=array();
			$info['product']=array();
		}

		//print_r($info);

		echo json_encode($info);
		exit;
	}


	function sendemail(){
		$id=trim($_GET['id']);
		$catid=trim($_GET['catid']);
		$type=trim($_GET['type']);
		$val=trim($_GET['v']);
		$config = F('Config'); 
		$info= M('Deposit')->where("id=".$id)->find();
		$orders=M('Order')->where("sn=".$info['ordersn'])->field('id,consignee,userid')->find();
		$product=M('Order_data')->where("order_id=".$orders['id'])->field('product_id')->order('id desc')->find();

		$data=M('Emtpl')->find($type);

		if($data){
			$header = str_replace(array('{consignee}','{sn}'),array($orders['consignee'],$val),$data['title']);
			$message = str_replace(array('{ordersn}','{sn}'),array($info['ordersn'],$val),$data['description']);
		}

		if($info['email']){
			$to = $info['email'] . ',';
		}
		if($info['emails']){
			$to .= $info['emails'] . ',';
		}
		if($info['emailss']){
			$to .= $info['emailss'] . ',';
		}

		if($to && $data['status']==1){
			$r = sendmail($to,$header,$message,$this->Config);
		}

		$arr=array();
		$arr['posid']=1;
		$arr[$catid]=$val;
		M('Deposit')->where("id=".$id)->save($arr);



		$arrs=array();
		$arrs['actid']=$type;
		M('Order')->where("id=".$orders['id'])->save($arrs);

		$pro="佐川番号：".$val;
		
		$array['title']=$pro;
		$array['catid']=29;
		$array['status']=1;
		$array['createtime']=time();
		$array['updatetime']=time();
		$array['product_id']=$product['product_id'];
		$array['wurl']=$val;
		$array['url']='/sagawas-'.$val.'.html';
		$array['keywords']=$val;
		M('Sagawas')->add($array);

		$qas['order_id']=$orders['id'];
		$qas['userid']=$orders['userid'];
		$qas['post_time']=time();
		$qas['back_time']=time();
		$qas['msg_content']=$message;
		$qas['feedback']=$message;
		$qas['msg_name']=$header;
		$qas['typeid']=1;
		$qas['howis']=1;
		M('Qas')->add($qas);

		echo 1;
		exit;
	}

	function sendemails(){
		$id=$_GET['id'];
		$catid=$_GET['catid'];
	
		$config = F('Config');
		$info= M('Deposit')->find($id);
		$orders=M('Order')->where("sn=".$info['ordersn'])->field('id,consignee')->find();

		$tpl=$config['mail_cuih'];
		$title="お世話様です、".$info['ordersn'];

		$message = str_replace(array('{ordersn}','{sn}'),array($info['ordersn'],$val),$tpl);
	
		if($info['email']){
			$to = $info['email'] . ',';
			//$r = sendmail($info['email'],$title,$message,$this->Config);
		}
		if($info['emails']){
			$to .= $info['emails'] . ',';
			//$r = sendmail($info['emails'],$title,$message,$this->Config);
		}
		if($info['emailss']){
			$to .= $info['emailss'] . ',';
			//$r = sendmail($info['emailss'],$title,$message,$this->Config);
		}

		if($to){
			$r = sendmail($to,$title,$message,$this->Config);
		}
		
		$arr=array();
		$arr['thumb']=1;
		M('Deposit')->where("id=".$id)->save($arr);

		$array['order_id']=$orders['id'];
		$array['post_time']=time();
		$array['back_time']=time();
		$array['msg_content']=$title;
		$array['feedback']=$message;
		$array['msg_name']=$orders['consignee'];
		M('Qas')->add($array);

		echo 1;
		exit;
	}

	function lists(){
		
	}
	function Lottery(){
		$lifeTime = 24 * 3600;
		session_set_cookie_params($lifeTime);
		session_start();

		if (!isset($_SESSION['send_time']))
        {
            $_SESSION['send_time'] = 0;
        }
		$cur_time = time();
        if (($cur_time - $_SESSION['send_time']) < 60*60*24)
        {
            echo "1日1回のみ";
			exit;
        }
		$email=trim($_POST['email']);
		$id=trim($_POST['id']);

		if($id==1){
			$title="全額還元 100% ";
		}
		if($id==2){
			$title="最大還元 30%  ";
		}
		if($id==3){
			$title="無料福袋 ";
		}
		if($id==4){
			$title="2000クーポン ";
		}
		if($id==5){
			$title="1500クーポン ";
		}
		if($id==6){
			$title="1000クーポン ";
		}
		if($id==7){
			$title="500クーポン ";
		}
		$map['title']=$title;
		$map['createtime']=time();
		$map['email']=$email;
		$map['catid']=47;
		$map['status']=1;
		$map['keywords']=$id;
		$id=M('Lottery')->add($map);

		$_SESSION["send_time"] = $cur_time;
		echo "おめでとうございます、Bibicopyへのサブスクライブに成功しました！";
		$this->lotter($id);
	}

	function faq(){
		if(!$_POST && !isset($_COOKIE['TP_userid'])){
			throw_exception('404');
			exit;
		}else{
			$info = M('User')->find($_COOKIE['TP_userid']);


			$data = get_safe_replace($_POST);
			$data['ip'] = get_client_ip();
			$data['createtime'] = time();
			$data['status'] = 0;
			$data['title'] = $_POST['prod'];
			$data['keywords'] = $_POST['bodys'];
			$data['description'] = $_POST['bodys'];
			$data['email'] = $info['email'];
			$data['uname'] = $info['realname'];
			$data['pids'] = $_POST['idss'];
			$data['catid']=48;
			$data['content']='';
			$data['userid'] = $_COOKIE['TP_userid'];
			$res = M('Faq')->add($data);
			if($res){

				exit(json_encode([
					'status' => 1,
					'msg' => '正常に送信されました'
				]));

			}else{

				error_log($model->getDbError());

				exit(json_encode([
					'status' => 0,
					'msg' => '送信失敗'
				]));
			}


		}
	}

	function lotterys(){
		$id=$_GET['id'];
		$info = M('Lottery')->find($id);
		$config = F('Config');
		$levs=$info['keywords'];
		if($levs){
			$data=M('Coupon')->where("keywords=".$levs)->find();
			$tpl=$config['mail_lot'];
			$message = str_replace(array('{code}','{title}'),array($data['bian'],$info['title']),$tpl);
			$r = sendmail($info['email'],'【BiBicopy】イベントのお知らせ',$message,$this->Config);
			M('Lottery')->where('id='.$id)->setInc('hits');
			echo "12";
			exit;
		}
		echo "222";
		exit;
	}

	function lotter($id){
		$info = M('Lottery')->find($id);
		$config = F('Config');
		$levs=$info['keywords'];
		if($levs){
			$data=M('Coupon')->where("keywords=".$levs)->find();
			$tpl=$config['mail_lot'];
			$message = str_replace(array('{code}','{title}'),array($data['bian'],$info['title']),$tpl);
			//	$r = sendmail($info['email'],'【BiBicopy】イベントのお知らせ',$message,$this->Config);
			M('Lottery')->where('id='.$id)->setInc('hits');
			echo "12";
			exit;
		}
		echo "222";
		exit;
	}


	function Sowr(){
		$id=$_POST['orderid'];
		$field =  'a.*,b.nypos,b.ishas';
		$order_data = M()->field($field)->table('div_order_data as a')->join("left join  div_product b on a.product_id=b.id")->where("a.order_id=".$id)->select();
		$zipcode = M('Order')->field('zipcode')->find($id);

		$html.='<table width="100%" border="0" cellspacing="0" class="table_form" cellpadding="0" align="center" style="margin-top: 0;">		 
		<tbody><tr><td colspan="6" style=" color: #000;">注文附註：'.$zipcode['zipcode'].'</td></tr><tr align="center" style="background:#F8F8F8;color: #000;">
		  <td width="15%">商品画像</td>
		  <td width="30%">商品</td>
		  <td width="10%">販売価格</td>
		  <td width="10%">库存</td>
		  <td width="10%">存货</td>
		  <td width="12%">数量</td>
		</tr>';
		foreach($order_data as $vo){
			$nypos=$vo['nypos']?'<a href="javascript:;" data-id="0" data-type="'.$vo['product_id'].'"><font color="#f409b7">无货</font></a>':'<a href="javascript:;" data-id="1" data-type="'.$vo['product_id'].'"><font color="black">无货</font></a>';
			$ishas=$vo['ishas']?'<a href="javascript:;" data-id="0" data-type="'.$vo['product_id'].'"><font color="#f409b7">在库</font></a>':'<a href="javascript:;" data-id="1" data-type="'.$vo['product_id'].'"><font color="black">在库</font></a>';
			$html.='<tr align="center">
			<td height="100" style="border-left:none;"><a href="'.$vo['product_url'].'" target="_blank"><img src="'.$vo['product_thumb'].'" width="100" height="100"/></a></td>
			<td><a href="'.$vo['product_url'].'" target="_blank">'.$vo['product_name'].'</a></td>
			<td>'.$vo['product_price'].'</td>
			<td  class="acts">'.$nypos.'</td>
			<td class="ishas">'.$ishas.'</td>
			<td>'.$vo['number'].'</td>
		 </tr>';
		}
		$html.='</tbody></table>';
		echo $html;
	}
	public  function trees($id){
		$list=M('Protype')->where('parentid = '.$id.' and status = 1')->field('id,parentid,name,catdir,brandid')->order("listorder asc,id desc")->select();
		foreach($list as $k => $v)
		$html .= "<option value='{$v['id']}' data-id='{$v['brandid']}'>{$v['catdir']} {$v['name']}</option>";        
		exit($html);
	}
	public function love(){
		 if($_POST['id'] && $this->_userid){
			$data=M('Product')->field('pinpai,protype')->find($_POST['id']);
			$userid=$this->_userid;
			$useds=M('User_collect')->where("userid={$userid} AND proid=".$_POST['id'])->count();
			if(!$useds){
				$info['proid']=$_POST['id'];
				$info['userid']=$this->_userid;
				$info['protype']=$data['protype'];
				$info['pinpai']=$data['pinpai'];
				$info['ip']=get_client_ip();
				$info['time']=time();
				M('User_collect')->add($info);
				$arr['typeid']=0;
				$arr['userid']=$this->_userid;
				$arr['username']=$this->_userid;
				$arr['note']='收藏了商品'.trim($_POST['id']);
				$arr['ip']=get_client_ip();
				$arr['time']=time();
				M('User_log')->add($arr);
				$arr = array( 
					'status'=>2, 
					'msg'=>'', 
					'data'=>'', 
				); 
				echo json_encode($arr);
				exit;
			}else{
				$arr = array( 
					'status'=>3, 
					'msg'=>'あなたはすでにこのアイテムをお気に入りにしています', 
					'data'=>'', 
				); 
				echo json_encode($arr);
				exit;
			}
		 }else{
			$arr = array( 
				'status'=>3, 
				'msg'=>'ログイン後のお気に入り', 
				'data'=>'', 
			); 
			echo json_encode($arr);
			exit;
		 }
	}
   public function multiStatus(){
		$ids = isset($_REQUEST['ids']) ? $_REQUEST['ids'] : [];
		if (!is_array($ids) || empty($ids)) {
			echo json_encode(['stats' => []]);
			exit;
		}
		$map['proid'] = array('IN', $ids);
		$map['userid'] = $this->_userid;

		$result = M('User_collect')
			->where($map)
			->group('proid')
			->field('proid, COUNT(*) as total')
			->select();
		$stats = [];
		foreach ($result as $row) {
			$stats[$row['proid']] = ['total' => intval($row['total'])];
		}
		echo json_encode(['stats' => $stats]);
		exit;
   }
   	public function hits(){
		$id=intval($_REQUEST['id']);
		if($id==41295){
			return false;
		}
		if($id){
			$data=M('Product')->field('hits')->where(array('id'=>$id))->find();
			$count =M('Reply')->where('status = 1 and artid = '.$id)->count();
			if($data['hits']==120 && $count<1){
				comment($id,1);
			}
		}else{
			return false;
		}
	}


	public function getComments(){
		$type = $_REQUEST['type'] ? $_REQUEST['type'] : 'protype';
        $page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
        $limit = $_REQUEST['limit'] ? $_REQUEST['limit'] : 5;
		$artid=intval($_REQUEST['id']);
		$Review = M('Reply');


		if($page>2){
			$userid = isset($_COOKIE['TP_userid']) ? intval($_COOKIE['TP_userid']) : 0;
			if ($userid <= 0) {
				return $this->ajaxReturn(['login' => 0]);
			}
		}


        $where = [];
		$where['status'] = 1;
		$where['isreal'] = 1;
        if ($type === 'protype') {
			$wprotype = findSelfAndChildren($artid);
			$where['protype'] = array('in', $wprotype);
			$total = $Review->where($where)->count();
			if($total==0){
				$topid=Parentid($artid);
				$protype=F('Protype');
				$wprotype=trim(get_arrchildid($topid,$protype),',');
				$where['protype'] = array('in', $wprotype);
				$total = $Review->where($where)->count();
			}

        } elseif ($type === 'pinpai') {
            $where['pinpai']=$artid;
			 $total = $Review->where($where)->count();
        } else {
            $this->ajaxReturn(['list'=>[], 'has_more'=>false]);
            return;
        }
        
       
        $offset = ($page - 1) * $limit;
        $list = $Review->where($where)
		->field('id,artid,rating,username,content,createtime,thumbs,isreal')
		->order("CASE WHEN thumbs IS NOT NULL AND thumbs != '' THEN 0 ELSE 1 END,isreal DESC, createtime DESC, id DESC")
		->limit($offset, $limit)
		->select();


        $result = [];
        foreach ($list as $item) {
            $images = [];
            if (!empty($item['thumbs'])) {
                $imgs = json_decode($item['thumbs'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $images = $imgs;
                } else {
                    $images = explode(',', $item['thumbs']);
                    $images = array_filter($images, function($v){ return !empty($v); });
                }
            }
			$info=M('Product')->where(['id'=>$item['artid']])->find();

			if($info['sales']){
				$pro_price=number_format($info['pro_price']-($info['pro_price']*$info['sales']/100));
			}else{
				$pro_price=number_format($info['pro_price']);
			}
            $result[] = [
				'id' => intval($item['id']),
                'stars' => intval($item['rating']),
                'author' => $item['username'],
                'content' => $item['content'],
				'product_id' => $info['id'],
				'product_url' => $info['url'],
				'product_img' => $info['thumb'],
				'product_title' => $info['title'],
				'product_price' => $pro_price,
                'images' => $images,
                'date' => date('Y-m-d', $item['createtime']),
            ];
        }
        $has_more = ($page * $limit) < $total;
        $this->ajaxReturn(['list' => $result, 'total'=>$total,'has_more' => $has_more]);
	}
	public function pageComments() {
		$type   = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'all';
		$page   = max(1, intval($_REQUEST['page']));
		$limit  = 15;
		$offset = ($page - 1) * $limit;
		$startTime = strtotime('-1 year');


		if($page>1){
			$userid = isset($_COOKIE['TP_userid']) ? intval($_COOKIE['TP_userid']) : 0;
			if ($userid <= 0) {
				return $this->ajaxReturn(['status' => 0]);
			}
		}

		$where = array(
			'status'     => 1,
			'createtime' => array('gt', $startTime),
		);

		switch ($type) {
			case 'good':  $where['rating'] = array('egt', 4); break;
			case 'min':   $where['rating'] = 3; break;
			case 'bad':   $where['rating'] = array('elt', 2); break;
			case 'thumb': $where['thumbs'] = array('neq', ''); break;
		}

		$list = M('Reply')
			->where($where)
			->order("isreal DESC, createtime DESC, id DESC")
			->limit($offset . ',' . $limit)
			->select();

		if (!$list) {
			$this->ajaxReturn(array('status' => 0, 'data' => array(), 'limit' => $limit), 'JSON');
			return;
		}

		$artIds = array_column($list, 'artid');
		$artIds = array_unique($artIds);

		$products = M('Product')
			->where(array('id' => array('in', $artIds)))
			->getField('id,title,url,thumb');
		$data = array();
		foreach ($list as $vo) {
			$thumbsArr = $vo['thumbs'] ? explode(',', $vo['thumbs']) : array();
			$prod      = isset($products[$vo['artid']]) ? $products[$vo['artid']] : array();

			$data[] = array(
				'id'             => $vo['id'],
				'username'       => $vo['username'],
				'rating'         => intval($vo['rating']),
				'content'        => $vo['content'],
				'content_length' => mb_strlen($vo['content'], 'utf-8'),
				'description'    => $vo['description'],
				'date'           => date('Y.m.d', $vo['createtime']),
				'title'          => isset($prod['title']) ? $prod['title'] : '',
				'url'            => isset($prod['url']) ? $prod['url'] : '',
				'thumb_main'     => isset($prod['thumb']) ? $prod['thumb'] : '',
				'thumbs'         => $thumbsArr
			);
		}

		$this->ajaxReturn(array(
			'status' => 1,
			'data'   => $data,
			'limit'  => $limit
		), 'JSON');
	}
   public function islogin(){
		$userid=intval($_REQUEST['userid']);
		$info=M('User')->where('id='.$userid)->field('status')->find();
		if($info['status']==0){
			cookie(null,'TP_');
			echo json_encode(1);
		}else{
			echo json_encode(2);
		}		
   }


   
	public function getCounts(){
		$userid = isset($_COOKIE['TP_userid']) ? intval($_COOKIE['TP_userid']) : 0;
		if ($userid <= 0) {
			$msgs=0;
		}else{
			$times = strtotime("-30 days");
			$where = [
				'userid'   => $userid,
				'post_time'=> ['gt', $times],
				'howis'    => 1,
				'isread'   => 0,
			];
			$msgs = (int)M('Qas')->where($where)->count();
		}

		$sessionid = cookie('onlineid');
		if (empty($sessionid)) {
			$number=0;
		}else{
			$number = (int)MD('Cart', 'sessionid="' . addslashes($sessionid) . '"', 'sum(number)');
		}

		$this->ajaxReturn(['msg'=>$msgs,'cart'=>$number]);
	}


	/**
	 * Apple 风格 - 验证码（PNG）
	 *  GET ?m=Ajax&a=verify
	 */
	public function verify(){
		header('Content-Type: image/png');
		$code = $this->_randCode(4);
		cookie('verify_code', strtolower($code), 600);
		$w = 90; $h = 32;
		$im = imagecreatetruecolor($w, $h);
		$bg = imagecolorallocate($im, 245, 245, 247);
		$fg = imagecolorallocate($im, 29, 29, 31);
		imagefilledrectangle($im, 0, 0, $w, $h, $bg);
		for ($i=0; $i<200; $i++) {
			$c = imagecolorallocate($im, mt_rand(180,230), mt_rand(180,230), mt_rand(180,230));
			imagesetpixel($im, mt_rand(0,$w), mt_rand(0,$h), $c);
		}
		for ($i=0; $i<4; $i++) {
			imagestring($im, 5, 14 + $i*18, mt_rand(8,12), $code[$i], $fg);
		}
		imagepng($im); imagedestroy($im);
		exit;
	}

	/**
	 * Apple 风格 - 点赞商品
	 */
	public function like(){
		$id = (int)$_REQUEST['id'];
		if ($id <= 0) $this->ajaxReturn(0, 'パラメータエラー', 0);
		M('Product')->where('id='.$id)->setInc('hits', 1);
		$row = M('Product')->field('hits')->find($id);
		$this->ajaxReturn(['hits'=>(int)$row['hits']], '已点赞', 1);
	}

	/**
	 * Apple 风格 - 搜索建议
	 */
	public function suggest(){
		$q = trim((string)$_REQUEST['q']);
		if ($q === '') $this->ajaxReturn([], '', 1);
		$rows = M('Product')->field('id,title,thumb,pro_price')
			->where(['status'=>1, 'title'=>['LIKE', '%'.$q.'%']])
			->order('id DESC')->limit(8)->select();
		$out = [];
		foreach ($rows as $r) {
			$r['thumb_url'] = $r['thumb'] ? $r['thumb'] : '__STYLE__/css/placeholder.svg';
			$r['url'] = '/index.php?m=Product&a=show&id='.$r['id'];
			$r['price_text'] = $r['pro_price'] ? number_format((float)$r['pro_price']).'円' : '';
			$out[] = $r;
		}
		$this->ajaxReturn($out, '', 1);
	}

	/**
	 * Apple 风格 - 邮件订阅
	 */
	public function subscribe(){
		$email = trim((string)$_REQUEST['email']);
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$this->ajaxReturn('', 'メールアドレスを正しく入力してください', 0);
		}
		$M = M('EmailSendlist');
		if ($M->where(['email'=>$email])->find()) {
			$this->ajaxReturn('', 'すでに登録されています', 1);
		}
		$M->add(['email'=>$email, 'status'=>1, 'createtime'=>time()]);
		$this->ajaxReturn('', 'ご購読ありがとうございます！', 1);
	}

	/**
	 * Apple 风格 - 留言提交
	 */
	public function feedback(){
		$data = [
			'title'      => trim((string)$_REQUEST['title']),
			'content'    => trim((string)$_REQUEST['content']),
			'uname'      => trim((string)$_REQUEST['uname']),
			'email'      => trim((string)$_REQUEST['email']),
			'tel'        => trim((string)$_REQUEST['tel']),
			'createtime' => time(),
			'status'     => 0,
		];
		if ($data['title']==='' || $data['content']==='') {
			$this->ajaxReturn('', '件名と内容を入力してください', 0);
		}
		$uid = isset($_COOKIE['TP_userid']) ? (int)$_COOKIE['TP_userid'] : 0;
		$data['userid'] = $uid;
		$data['username'] = $uid ? '' : $data['uname'];
		M('Feedback')->add($data);
		$this->ajaxReturn('', 'ご送信ありがとうございます。担当者よりご連絡いたします。', 1);
	}

	/**
	 * Apple 风格 - 切换收藏（Ajax）
	 */
	public function toggleFav(){
		$uid = isset($_COOKIE['TP_userid']) ? (int)$_COOKIE['TP_userid'] : 0;
		if ($uid <= 0) $this->ajaxReturn(['code'=>401], 'ログインしてください', 0);
		$id = (int)$_REQUEST['id'];
		$op = $_REQUEST['op'] === 'del' ? 'del' : 'add';
		$M  = M('UserCollect');
		$has = $M->where(['userid'=>$uid, 'proid'=>$id])->find();
		if ($op === 'del') {
			if ($has) $M->where(['userid'=>$uid, 'proid'=>$id])->delete();
			$this->ajaxReturn(['code'=>0, 'op'=>'del'], 'お気に入りを解除しました', 1);
		}
		if (!$has) {
			$M->add(['userid'=>$uid, 'proid'=>$id, 'time'=>time(), 'ip'=>get_client_ip()]);
		}
		$this->ajaxReturn(['code'=>0, 'op'=>'add'], 'お気に入りに追加しました', 1);
	}

	/**
	 * 验证码校验
	 */
	public function checkVerify(){
		$code = strtolower(trim((string)$_REQUEST['code']));
		$sess = strtolower(trim((string)cookie('verify_code')));
		if ($sess === '')        $this->ajaxReturn('', '验证码已过期', 0);
		if ($code !== $sess)     $this->ajaxReturn('', '验证码错误', 0);
		$this->ajaxReturn('', 'ok', 1);
	}

	private function _randCode($n){
		$str = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
		$out = '';
		for ($i=0; $i<$n; $i++) {
			$out .= $str[mt_rand(0, strlen($str)-1)];
		}
		return $out;
	}


}
?>