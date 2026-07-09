<?php
/**
 * 
 * OrderAction.class.php (前台购物订单)
 *
 */
if(!defined("ThinkPHP")) exit("Access Denied");
class ProductAction extends BaseAction{
	protected   $dao , $sessionid,$cdao;
	function _initialize()
    {
		parent::_initialize();
		$this->dao=M('Product');
		$this->sessionid =  cookie('onlineid');
		//$list = array('gucci', 'balenciaga','bottega','saint laurent','ボッテガ','バレンシアガ');
		$list = array('balenciaga','bottega','saint laurent');


		if(sensitive($list, $_REQUEST['keyword'])==1){
			header('HTTP/1.1 404 Moved Permanently');
			throw_exception('404');
			exit;
		}

    }
    public function show($id='',$module='')
    {
		
		$this->Urlrule =F('Urlrule');
		$p= max(intval($_REQUEST[C('VAR_PAGE')]),1);		
		$id = $id ? $id : intval($_REQUEST['id']);
		$module = $module ? $module : MODULE_NAME;
		$this->assign('module_name',$module);
		$this->dao= M($module);
		$data = $this->dao->find($id);

		if($data['stock']==1){
			throw_exception('404');
			exit;
		}
		
		$catid = $data['catid'];
		$cat = $this->categorys[$data['catid']];

		//print_r($cat);
		if(empty($cat['ishtml']))$this->dao->where("id=".$id)->setInc('hits'); //添加点击次数

		//comment($id);

		$bcid = explode(",",$cat['arrparentid']); 
		$bcid = $bcid[1]; 
		if($bcid == '') $bcid=intval($catid);

		if($data['readgroup']){
			if($this->_groupid!=1 && !in_array($this->_groupid,explode(',',$data['readgroup'])) )$noread=1;
		}elseif($cat['readgroup']){
			if($this->_groupid!=1 && !in_array($this->_groupid,explode(',',$cat['readgroup'])) )$noread=1;
		}
		if($noread==1){$this->assign('jumpUrl',URL('User-Login/index'));$this->error (L('NO_READ'));}

		$chargepoint = $data['readpoint'] ? $data['readpoint'] : $cat['chargepoint']; 
		if($chargepoint && $data['userid'] !=$this->_userid){
			$user = M('User');
			$userdata =$user->find($this->_userid);
			if($cat['paytype']==1 && $userdata['point']>=$chargepoint){
				$chargepointok = $user->where("id=".$this->_userid)->setDec('point',$chargepoint);
			}elseif($cat['paytype']==2 && $userdata['amount']>=$chargepoint){
				$chargepointok = $user->where("id=".$this->_userid)->setDec('amount',$chargepoint);
			}else{
				$this->error (L('NO_READ'));
			}
		}
		$off='';
		if($data['sales'] &&  ($data['sales']>$data['pro_price'])){
			$off='【'.(100 - round(($data['pro_price']/$data['sales'])*100)).'%OFF💰】';
		}
		$seo_title = $data['title'].'-'.$cat['catname'];
		$this->assign ('off',$off);
		$this->assign ('seo_title',$seo_title);
		$this->assign ('seo_keywords',$data['keywords']);
		$this->assign ('seo_description',$data['description']);
		$this->assign ( 'fields', F($cat['moduleid'].'_Field') ); 
		

		$fields = F($this->mod[$module].'_Field');
		// var_dump($fields);
		foreach($data as $key=>$c_d){
			$setup='';
			$fields[$key]['setup'] =$setup=string2array($fields[$key]['setup']);
			if($setup['fieldtype']=='varchar' && $fields[$key]['type']!='text' && $fields[$key]['type']!='typeid'){
				$data[$key.'_old_val'] =$data[$key];
				$data[$key]=fieldoption($fields[$key],$data[$key]);
			}elseif($fields[$key]['type']=='images' || $fields[$key]['type']=='files'){ 
				if(!empty($data[$key])){
					$p_data=explode(':::',$data[$key]);
					$data[$key]=array();
					foreach($p_data as $k=>$res){
						$p_data_arr=explode('|',$res);					
						$data[$key][$k]['filepath'] = $p_data_arr[0];
						$arr=explode('lieren',  $p_data_arr[1]);
						$data[$key][$k]['filename'] = $arr[0];
						$data[$key][$k]['filealt'] = $arr[1];
					}
					unset($arr);
					unset($p_data);
					unset($p_data_arr);
				}
				if(!is_array($data[$key])) $data[$key] = array();
			}
			unset($setup);
		}
	
		$this->assign('fields',$fields); 


		//手动分页
		$CONTENT_POS = strpos($data['content'], '[page]');
		if($CONTENT_POS !== false) {
			
			$urlrule = geturl($cat,$data,$this->Urlrule);
			$urlrule =  str_replace('%7B%24page%7D','{$page}',$urlrule); 
			$contents = array_filter(explode('[page]',$data['content']));
			$pagenumber = count($contents);
			for($i=1; $i<=$pagenumber; $i++) {
				$pageurls[$i] = str_replace('{$page}',$i,$urlrule);
			} 
			$pages = content_pages($pagenumber,$p, $pageurls);
			//判断[page]出现的位置是否在文章开始
			if($CONTENT_POS<7) {
				$data['content'] = $contents[$p];
			} else {
				$data['content'] = $contents[$p-1];
			}
			$this->assign ('pages',$pages);	
		}

		if(!empty($data['template'])){
			$template = $data['template'];
		}elseif(!empty($cat['template_show'])){
			$template = $cat['template_show'];
		}else{
			$template =  'show';
		}
		$hisids=explode(',', cookie('htrids'));
		if($hisids){
			if(!in_array($data['id'], $hisids)){
				$oldhtrids=trim(cookie('htrids').','.$data['id'],',');
				cookie('htrids',$oldhtrids);
			}
		}else{
			cookie('htrids',$data['id']);
		}
		// var_dump(cookie('htrids'));exit;
		$count =M('Reply')->where('status = 1 and artid = '.$id)->count();
		if($data['hits']==110 && $count<1){
			//new_comment($id);
		}
		/*
		if($data['hits']==500 && $id>40252){
			bad_comment($id);
		}
*/
		if($count){
			/*import ( "@.ORG.Page" );
			$listRows =  !empty($cat['pagesize']) ? $cat['pagesize'] : C('PAGE_LISTROWS');		
			$page = new Page ( $count, $listRows );
			$page->urlrule = geturl($cat,'');
			$pagesr = $page->show();*/
			$field =  'username,title,content,thumb,artid,ip,createtime,thumbs,rating,description';
			$listr = M('Reply')->field($field)->where('status = 1 and artid = '.$id)->order('istop = 1 desc , createtime desc,id desc')->limit(5)->select();
			// var_dump($listr);
			//$this->assign('pages',$pagesr);
			$this->assign('listr',$listr);

			$sum=M('Reply')->where('status = 1 and artid = '.$id)->sum('rating');
			if($sum){
			 	$avg=round($sum/$count,1);
			}else{
				$avg=5;
			}
			$this->assign ('avg',$avg);	
		}

		$arr=array(5,4,3,2,1);
		$rating=array();
		foreach($arr as $r){
			$rating[$r]=M('Reply')->where('status = 1 and artid = '.$id.' and rating='.$r)->count();
		}

		$this->assign ('rating',$rating);
		$this->assign ('totalreview',$count);


		$this->assign('catid',$catid);
		$this->assign ($cat);
		$this->assign('bcid',$bcid);
		// var_dump($data['protype']);
		if($data['protype']){
				$protype=F('Protype');
				$seo=$protype[$data['protype']];
				$this->assign('mseo_title',$seo['seo_title']);
				$this->assign('mseo_keywords',$seo['seo_keywords']);
				$this->assign('mseo_description',$seo['seo_description']);
				$this->assign('mseo_content',$seo['seo_content']);
				$this->assign('seo_bigfooter',$seo['seo_bigfooter']);

				$typecatpos=get_arrparentid($data['protype'],$protype);

			

				$catpos=explode(',', $typecatpos);
				$str='';

				$ii=2;
				foreach ($catpos as $key => $value) {
					if($value==0){
						$catpos[$key]=$seo;
						$top=$seo;
						continue;
						
					}else{
						$str .='<font itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"><a href="/'.$protype[$value]['catdir'].'/"  itemprop="item"><span itemprop="name">'.$protype[$value]['name'].'</span></a><meta itemprop="position" content="'.$ii.'" /></font>&gt;';
						//$str .=' <font  itemscope itemtype="http://data-vocabulary.org/Breadcrumbs" itemprop="child"><a itemprop="url" href="/'.$protype[$value]['catdir'].'/"> <font itemprop="title">'.$protype[$value]['name'].'</font></a></font> &gt; ';
						$top=$protype[$value];
					}
					$ii++;
				}
				//$str .=' <font  itemscope itemtype="http://data-vocabulary.org/Breadcrumbs" itemprop="child"><a href="/'.$seo['catdir'].'/" itemprop="url"> <font itemprop="title">'.$seo['name'].'</font></a></font> &gt; ';
				$str .='<font itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"><a href="/'.$seo['catdir'].'/"  itemprop="item"><span itemprop="name">'.$seo['name'].'</span></a><meta itemprop="position" content="'.$ii.'" /></font>&gt;';
				$this->assign('catpos',trim($str,'&gt;'));
				$this->assign('top',$top);
				unset($top);
				unset($seo);
				unset($protype);
			}
		$this->assign ($data);
		$config = F('Config');
		$pinpaix=F('Brand');

		$product['title']=$data['title'];
		$product['thumb']=$config['site_url'].$data['thumb'];
		$product['description']=$data['content'];
		$product['url']=$config['site_url'].$data['url'];
		$product['pro_price']=$data['pro_price'];
		$product['sku']=$data['wurl'];
		$product['brand']=$pinpaix[$data['pinpai']]['title'];
		$product['review_count']=$count;
		$product['rating']=$avg;
		$product['in_stock']=$data['hits'];
		if($count){
			$structured=build_product_structured_data($product);
			$this->assign('structured',$structured);
		}

		$this->display($module.':'.$template); 
    }
	function review(){
		$module = $module ? $module : MODULE_NAME;

		$this->dao= M($module);
		$aid=$_GET['id'];
		$data = $this->dao->where("wurl='$aid'")->find();
		$id=$data['id'];
		$count =M('Reply')->where('status = 1 and artid = '.$id)->count();
		$protype=F('Protype');
		$typecatpos=get_arrparentid($data['protype'],$protype);
		$seo_title = $data['title'];
		$this->assign ('seo_title',$seo_title." の評価 一覧");
		$this->assign ('seo_keywords',$data['keywords']." の評価 一覧");
		$this->assign ('seo_description',$data['description']." の評価 一覧");

		$catpos=explode(',', $typecatpos);
		$str='';

		foreach ($catpos as $key => $value) {
			if($value==0){
				$catpos[$key]=$seo;
				$top=$seo;
				continue;
			}else{
				$str .='<font  itemscope itemtype="http://data-vocabulary.org/Breadcrumbs" itemprop="child"><a itemprop="url" href="/'.$protype[$value]['catdir'].'/"><font itemprop="title">'.$protype[$value]['name'].'</font></a></font>&gt;';
				$top=$protype[$value];
			}
		}
		$str .='<font  itemscope itemtype="http://data-vocabulary.org/Breadcrumbs" itemprop="child"><a href="/'.$seo['catdir'].'/" itemprop="url"><font itemprop="title">'.$seo['name'].'</font></a></font>&gt;';
		$this->assign('catpos',trim($str,'&gt;'));
		import ( "@.ORG.Page" );
		$listRows =  !empty($cat['pagesize']) ? $cat['pagesize'] : 20;
		$page = new Page ( $count, $listRows );

		$stre='/';
		if($_REQUEST['id']){
			$stre .='pro-'.$_REQUEST['id'];
		}
		$urlarr=array($stre.'-reviews.html',$stre.'-reviews-{$page}.html');

		$page->urlrule =$urlarr;

		$pages = $page->show();

		$field =  'username,title,content,thumb,artid,ip,createtime,rating,thumbs,description';
		$listr = M('Reply')->field($field)->where('status = 1 and artid = '.$id)->order('createtime desc,id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
		$lists = array();
		foreach ($listr AS $idx => $row){
			$arr['id'] =$row['id'];
			$arr['content'] =$row['content'];
			$arr['artid'] =$row['artid'];
			$arr['title'] =$row['title'];
			$arr['username'] =$row['username'];
			$arr['thumb'] =$row['thumb'];
			$arr['createtime'] =$row['createtime'];
			$arr['rating'] =$row['rating'];
			$arr['description'] =$row['description'];
			$arr['ip'] =$row['ip'];
			$arr['thumbs'] = $row['thumbs']?explode(",",$row['thumbs']):'';
			$lists[] = $arr;
			
		}

		$avg=M('Reply')->where('status = 1 and artid = '.$id)->avg('rating');
		$this->assign ('avg',round($avg,1));
		$this->assign ('count',$count);
		$this->assign('listr',$lists);
		$this->assign('pages',$pages);
		$this->assign('info',$data);
		$template =  'Product:reply';
		$this->display ($template);
		exit;
	}
}


?>