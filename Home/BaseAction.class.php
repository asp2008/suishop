<?php
/**
 * 
 * Base (前台公共模块)
 *
 */
if(!defined("ThinkPHP")) exit("Access Denied");
class BaseAction extends Action
{
	protected   $Config ,$sysConfig,$categorys,$module,$moduleid,$mod,$dao,$Type,$Role,$_userid,$_groupid,$_email,$_username ,$forward ,$user_menu,$Lang,$member_config;
    public function _initialize() {
			$this->sysConfig = F('sys.config');
			$this->module = F('Module');
			$this->Role = F('Role');
			$this->Type =F('Type');
			$this->mod= F('Mod');
			$this->moduleid=$this->mod[MODULE_NAME];
			if($_GET['pc']&&$_COOKIE['ispc']!=$_GET['pc']){
			         setcookie('ispc',$_GET['pc'],time()+3600);
			         echo '<script>window.location.reload(); </script>';
			}
			if(isset($_GET['pc'])&&$_GET['pc']==0&&$_COOKIE['ispc']!=0){
			     setcookie('ispc',null,time()-3600);
			     echo '<script>window.location.reload(); </script>';
			}

			$uri=$_SERVER['REQUEST_URI'];
			if($uri){
				$mpa ="title = '$uri'";
				$moved=M('Moved')->field('keywords')->where($mpa)->find();
				if($moved['keywords']){
					Access301($moved['keywords']);
				}
			}
			if(APP_LANG){
				$this->Lang = F('Lang');
				$this->assign('Lang',$this->Lang);
				if(get_safe_replace($_GET['l'])){
					if(!$this->Lang[$_GET['l']]['status'])$this->error ( L ( 'NO_LANG' ) );
					$lang=$_GET['l'];
				}else{
					$lang=$this->sysConfig['DEFAULT_LANG'];
				}
				define('LANG_NAME', $lang);
				define('LANG_ID', $this->Lang[$lang]['id']);
				$this->categorys = F('Category_'.$lang);
				$this->Config = F('Config_'.$lang);
				$this->assign('l',$lang);
				$this->assign('langid',LANG_ID);
				$T = F('config_'.$lang,'', APP_PATH.'Tpl/Home/'.$this->sysConfig['DEFAULT_THEME'].'/');
				C('TMPL_CACHFILE_SUFFIX','_'.$lang.'.php');
				cookie('think_language',$lang);
			}else{
				$T = F('config_'.$this->sysConfig['DEFAULT_LANG'],'',  APP_PATH.'Tpl/Home/'.$this->sysConfig['DEFAULT_THEME'].'/');
				$this->categorys = F('Category');
				$this->Config = F('Config');
				cookie('think_language',$this->sysConfig['DEFAULT_LANG']);
			}
			$this->assign('T',$T);
			$this->assign($this->Config);
			$this->assign('Role',$this->Role);
			$this->assign('Type',$this->Type);
			$this->assign('Module',$this->module);
			$this->assign('Categorys',$this->categorys);
			import("@.ORG.Form");			
			$this->assign ( 'form',new Form());
			C('HOME_ISHTML',$this->sysConfig['HOME_ISHTML']);
			C('PAGE_LISTROWS',$this->sysConfig['PAGE_LISTROWS']);
			C('URL_M',$this->sysConfig['URL_MODEL']);
			C('URL_M_PATHINFO_DEPR',$this->sysConfig['URL_PATHINFO_DEPR']);
			C('URL_M_HTML_SUFFIX',$this->sysConfig['URL_HTML_SUFFIX']);
			C('URL_LANG',$this->sysConfig['DEFAULT_LANG']);
			C('DEFAULT_THEME_NAME',$this->sysConfig['DEFAULT_THEME']);
			import("@.ORG.Online");
			$session = new Online();
			if(cookie('auth')){
				$thinkphp_auth_key = sysmd5($this->sysConfig['ADMIN_ACCESS'].$_SERVER['HTTP_USER_AGENT']);
				list($userid,$groupid, $password) = explode("-", authcode(cookie('auth'), 'DECODE', $thinkphp_auth_key));
				$this->_userid = intval($userid);
				$this->_username =  cookie('username');
				$this->_groupid = $groupid; 
				$this->_email =  cookie('email');
			}else{
				$this->_groupid = cookie('groupid') ?  cookie('groupid') : 4;
				$this->_userid =0;
			}
			//双核浏览器登录问题
			if(!$this->_userid){
				$this->_userid = cookie('userid');
				$this->_username =  cookie('username');
				$this->_groupid = cookie('groupid'); 
				$this->_email =  cookie('email');
			}
			$this->assign('web_userid',$this->_userid);
			$this->assign('web_username',$this->_username);
			foreach((array)$this->module as $r){
				if($r['issearch'])$search_module[$r['name']] = L($r['name']);
				if($r['ispost'] && (in_array($this->_groupid,explode(',',$r['postgroup']))))$this->user_menu[$r['id']]=$r;
			}
			$langext = $lang ? '_'.$lang : '';
			$this->member_config=F('member.config'.$langext);
			$this->assign('member_config',$this->member_config);
			$this->assign('user_menu',$this->user_menu);
			/*
			if(GROUP_NAME=='User'){	
				if($this->_groupid=='5' &&  MODULE_NAME!='Login'){ 
					$this->assign('jumpUrl',URL('User-Login/emailcheck'));
					$this->assign('waitSecond',3);
					$this->success(L('no_regcheckemail'));
					exit;
				}
				$this->assign('header',TMPL_PATH.'Home/'.THEME_NAME.'/Home_header.html');
			}
			*/
			if($_REQUEST['protype']){
				$protype=F('Protype');
				$seo=$protype[$_GET['protype']];
				
				$pagetitlte = isset($_GET['p']) ? intval($_GET['p']) : 1;
				$pageSuffix = $pagetitlte > 1 ? $pagetitlte.'ページ目' : '';
				$filterMap = array(
					'update' => 'おすすめ順',
					'hot'    => '人気順',
					'new'    => '新着順',
					'price'  => '価格が安い',
				);
				$ftitle = '';
				if (!empty($_GET['filter']) && isset($filterMap[$_GET['filter']])) {
					$ftitle = $filterMap[$_GET['filter']] . ' ';
				}
				$this->assign('mseo_title',
					$ftitle . $seo['seo_title']
				);
				$this->assign('page_seo_title',' '.$pageSuffix);

				$this->assign('mseo_keywords',
					$pageSuffix . $ftitle . $seo['seo_keywords']
				);
				$this->assign('mseo_description',
					$ftitle . $seo['seo_description']
				);



				$this->assign('mseo_content',$seo['seo_content']);
				$this->assign('seo_bigfooter',$seo['seo_bigfooter']);
				$typecatpos=get_arrparentid($_GET['protype'],$protype);
				$catpos=explode(',', $typecatpos);
				$str='';
				$ii=2;
				foreach ($catpos as $key => $value) {
					if($value==0){
						$catpos[$key]=$seo;
						$top=$seo;
						continue;
					}else{
						$str .='<font itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"><a href="/'.$protype[$value]['catdir'].'/"  itemprop="item"><span itemprop="name">'.$protype[$value]['name'].'</span></a><meta itemprop="position" content="'.$ii.'" /></font> &gt;';
						$top=$protype[$value];
					}
					$ii++;
				}
				$kk=$ii+1;
				$str .='<font itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"><a href="/'.$seo['catdir'].'/"  itemprop="item"><span itemprop="name">'.$seo['name'].'</span></a><meta itemprop="position" content="'.$ii.'" /></font> &gt;';
				$this->assign('catpos',trim($str,'&gt;'));
				$this->assign('top',$top);
				unset($top);
				unset($seo);
				unset($protype);
			}
			if($_GET['forward'] || $_POST['forward']){	
				$this->forward = get_safe_replace($_GET['forward'].$_POST['forward']);
			}else{
				if(MODULE_NAME!='Register' || MODULE_NAME!='Login' )
				$this->forward =isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] :  $this->Config['site_url'];
			}
			$this->assign('forward',$this->forward);
			$this->assign('search_module',$search_module);
			$this->assign('module_name',MODULE_NAME);
			$this->assign('action_name',ACTION_NAME);
	}
    public function index($catid='',$module='')
    {
		/*if($catid == 27){
			header('HTTP/1.1 500 Internal Server Error'); 
			header('Status: 500 Internal Server Error'); 
			//echo "HTTP/1.1 500 Internal Server Error";
			exit;
		}*/


		if($_REQUEST['pinpai']){
			$bb=M('Brand')->field('stock')->find($_REQUEST['pinpai']);
			if($bb['stock']==1){
				throw_exception('404');
				exit;
			}
		}
		if($_REQUEST['protype']){
			$bb=M('Protype')->field('stock')->find($_REQUEST['protype']);
			if($bb['stock']==1){
				throw_exception('404');
				exit;
			}
		}
		//echo date('YmdH',time());
		$this->Urlrule =F('Urlrule');
		if(empty($catid)) $catid =  intval($_REQUEST['id']);
		$p= max(intval($_REQUEST[C('VAR_PAGE')]),1);
		if($catid){
			$cat = $this->categorys[$catid];
			$bcid = explode(",",$cat['arrparentid']); 
			$bcid = $bcid[1]; 
			if($bcid == '') $bcid=intval($catid);
			if(empty($module))$module=$cat['module'];
			$this->assign('module_name',$module);
			unset($cat['id']);
			$this->assign($cat);
			$cat['id']=$catid;
			$this->assign('catid',$catid);
			$this->assign('bcid',$bcid);
		}
		if($cat['readgroup'] && $this->_groupid!=1 && !in_array($this->_groupid,explode(',',$cat['readgroup']))){$this->assign('jumpUrl',URL('User-Login/index'));$this->error (L('NO_READ'));}
		$fields = F($this->mod[$module].'_Field');
		foreach($fields as $key=>$r){
			$fields[$key]['setup'] =string2array($fields[$key]['setup']);
		}
		$this->assign ( 'fields', $fields); 
		$seo_title = $cat['title'] ? $cat['title'] : $cat['catname'];
		$this->assign ('seo_title',$seo_title);
		$this->assign ('seo_keywords',$cat['keywords']);
		$this->assign ('seo_description',$cat['description']);
		if($module=='Guestbook'){
			$where['status']=array('eq',1);
			$this->dao= M($module);
			$count = $this->dao->where($where)->count();
			if($count){
				import ( "@.ORG.Page" );
				$listRows =  !empty($cat['pagesize']) ? $cat['pagesize'] : C('PAGE_LISTROWS');		
				$page = new Page ( $count, $listRows );
				$page->urlrule = geturl($cat,'');
				$pages = $page->show();
				$field =  $this->module[$cat['moduleid']]['listfields'];
				$field =  $field ? $field : '*';
				$list = $this->dao->field($field)->where($where)->order('createtime desc,id desc')->limit($page->firstRow . ',' . $page->listRows)->select();
				$this->assign('pages',$pages);
				$this->assign('list',$list);
			}
			$template = $cat['module']=='Guestbook' && $cat['template_list'] ? $cat['template_list'] : 'index';
			$this->display(THEME_PATH.$module.'_'.$template.'.html');
		}elseif($module=='Page'){
			$modle=M('Page');
			$data = $modle->find($catid);
			unset($data['id']);
			if($catid==30){
				$template = $cat['template_list'] ? $cat['template_list'] :  'index' ;
				$this->assign ($data);	
				$this->display(THEME_PATH.$module.'_'.$template.'.html');
			}else{
				//分页
				$CONTENT_POS = strpos($data['content'], '[page]');
				if($CONTENT_POS !== false) {			
					$urlrule = geturl($cat,'',$this->Urlrule);
					$urlrule[0] =  urldecode($urlrule[0]);
					$urlrule[1] =  urldecode($urlrule[1]);
					$contents = array_filter(explode('[page]',$data['content']));
					$pagenumber = count($contents);
					for($i=1; $i<=$pagenumber; $i++) {
						$pageurls[$i] = str_replace('{$page}',$i,$urlrule);
					} 
					$pages = content_pages($pagenumber,$p, $pageurls);
					//判断[page]出现的位置
					if($CONTENT_POS<7) {
						$data['content'] = $contents[$p];
					} else {
						$data['content'] = $contents[$p-1];
					}
					$this->assign ('pages',$pages);	
				}
				$template = $cat['template_list'] ? $cat['template_list'] :  'index' ;
				$this->assign ($data);	
				$this->display(THEME_PATH.$module.'_'.$template.'.html');
			}
		}else{
			$mpinpai=$_REQUEST['pinpai'];
			if($mpinpai){
				$mseo=M('Brand')->field('mseo_title,mseo_keywords,mseo_description,description as b_description')->find($mpinpai);
				$this->assign($mseo);
				unset($mseo);
				unset($mpinpai);
			}
			if($catid){
				$seo_title = $cat['title'] ? $cat['title'] : $cat['catname'];
				$this->assign ('seo_title',$seo_title);
				$this->assign ('seo_keywords',$cat['keywords']);
				$this->assign ('seo_description',$cat['description']);

				$wprotype=$_REQUEST['protype'];
				$sextype=$_REQUEST['sextype'];
				$brand=$_REQUEST['pinpai'];
				$where = " status=1 ";
				$protype=F('Protype');
				$cid=$wprotype;
				if($wprotype) {
					$wprotype=trim(get_arrchildid($wprotype,$protype),',');
					if($wprotype)$where .=" and ( protype in(".$wprotype.') or FIND_IN_SET("'.$cid.'",`other_cat`) )';
				}
				unset($protype);
				if($sextype) $where .=" and (sextype = ".$sextype." or sextype ='')";
				if($brand) $where .=" and (pinpai = ".$brand." or pinpai =0 or bid= ".$brand." )";

				if($_GET['keyword']){
					checkey(trim($_GET['keyword']));
					headerto($_GET['keyword']);
					$this->assign ('bids',getbrandid(trim($_GET['keyword'])));
					$arr        = explode(' ', $_GET['keyword']);
					if(is_array($arr)){
						foreach ($arr AS $key => $val){
							if($val){
								$where .='  and (title like "%'.trim($val).'%" or content like "%'.trim($val).'%" or wurl like "%'.trim($val).'%" ) ';
							}
						}
					}else{
						$where .='  and (title like "%'.trim($_GET['keyword']).'%" or content like "%'.trim($_GET['keyword']).'%"  or wurl like "%'.trim($_GET['keyword']).'%") ';
					}
				}
				if($cat['child']){							
					$where .= " and catid in(".$cat['arrchildid'].")";			
				}else{
					$where .=  " and catid=".$catid;

				}
				if(empty($cat['listtype'])){
					$this->dao= M($module);
					$count = $this->dao->where($where)->count();
					if($brand){
						$atypes=$this->dao->where($where)->field('distinct(protype)')->select();
						$this->assign('atypes',$atypes);
					}
					if($count){



						$wprotypex=$_REQUEST['protype'];
						$protypex = F('Protype');
						$celebritys = explode(',', get_arrchildid(777, $protypex));

						
						import ( "@.ORG.Page" );
						

						if(isset($_GET['filter'])){
							if($_GET['filter']=='update'){				
								$orderby="updatetime desc,id desc";	
							}
							if($_GET['filter']=='hot'){							
								$orderby="buys desc,updatetime desc";	
							}
							if($_GET['filter']=='new'){							
								$orderby="id desc,updatetime desc";	
							}
							if($_GET['filter']=='price'){							
								$orderby="CAST(pro_price as SIGNED) asc,updatetime desc";	
							}
						}else{
							$orderby="updatetime desc,id desc";
						}
						$listRows =  !empty($cat['pagesize']) ? $cat['pagesize'] : C('PAGE_LISTROWS');
						$page = new Page ( $count, $listRows );
						if($cat['module']=='Product'&&!$_REQUEST['keyword']){
							$str='/';
							if($_REQUEST['pinpai']){
								$str .='brand_'.$_REQUEST['brand'];
							}
							if($_REQUEST['catdir']){
								$str .=$_REQUEST['catdir'];
							}
							if($_GET['filter']){
								$urlarr=array($str,$str.'_{$page}/?filter='.$_GET['filter']);
							}else{
								$urlarr=array($str,$str.'_{$page}/');
							}
							$page->urlrule =$urlarr;
						}elseif($_REQUEST['keyword']){
							$map['keyword']=$_REQUEST['keyword'];
							$map['id']=1;
							$map['p']='{$page}';
							$page->urlrule = URL('Home-Product/index',$map);
						}else{
							$page->urlrule = geturl($cat,'',$this->Urlrule);
						}
						$pages = $page->show();
						$field =  $this->module[$this->mod[$module]]['listfields'];
						$field =  $field ? $field : 'id,catid,userid,url,username,title,title_style,keywords,description,thumb,createtime,hits';
						if($catid==1){
							if(isset($_GET['filter']) && $_GET['filter']=='hot'){
								$hotProductIds = array();
								$hots=$where." and buys >= 3 ";
								$sqlHotIds = "SELECT id FROM div_product WHERE {$hots} ORDER BY id desc LIMIT 30";
								
								$resultHotIds = M()->query($sqlHotIds);

								foreach ($resultHotIds as $row) {
									$hotProductIds[] = $row['id'];
								}
								$resultHot = array();
								if (!empty($hotProductIds)) {
									$hotProductIdsStr = implode(",", $hotProductIds);
									$sqlHot = "SELECT * FROM div_product WHERE id IN ($hotProductIdsStr) ORDER BY id desc";
									$resultHot = M()->query($sqlHot);
								}
								if($resultHot){
									$where .= " AND id NOT IN (" . implode(",", $hotProductIds) . ")";
								}
								if(!isset($_REQUEST['p']) || $_REQUEST['p']==1){
									$page->listRows=$page->listRows-count($resultHot);
								}else{
									$page->firstRow=$page->firstRow-count($resultHot);
								}
								$resultAll = $this->dao->field($field)->where($where)->order('buys desc,updatetime desc')->limit($page->firstRow . ',' . $page->listRows)->select();

								//print_r($this->dao->getlastsql());
								if(!isset($_REQUEST['p']) || $_REQUEST['p']==1){
									if(!empty($resultHot) && !empty($resultAll)){
										$list = array_merge($resultHot, $resultAll);
									}elseif(empty($resultHot) && !empty($resultAll)){
										$list = $resultAll;
									}else{
										$list = $resultHot;
									}
								}else{
									$list = $resultAll;
								}
							}else{
								$list = $this->dao->field($field)->where($where)->order($orderby)->limit($page->firstRow . ',' . $page->listRows)->select();
							}

							//$list = $this->dao->field($field)->where($where)->order($orderby)->limit($page->firstRow . ',' . $page->listRows)->select();

						}else{
							$list = $this->dao->field($field)->where($where)->order('listorder asc,updatetime desc')->limit($page->firstRow . ',' . $page->listRows)->select();
						}
						$this->assign('pages',$pages);
						$this->assign('list',$list);
					}
					$count =$count >0 ? $count:0;
					$this->assign('count',$count);
					$arr=array('17','0');
					if(in_array($_REQUEST['pinpai'],$arr)){
						$mseo=M('Brand')->field('mseo_title,mseo_keywords,mseo_description,description as b_description')->find($_REQUEST['pinpai']);
						$this->assign('mseo_title',UnicodeEncode($mseo['mseo_title']));
						$this->assign('mseo_keywords',UnicodeEncode($mseo['mseo_keywords']));
						$this->assign('mseo_description',UnicodeEncode($mseo['mseo_description']));
					}
					$template_r = 'list';
				}else{
					$template_r = 'index';
				}
			}else{
				$template_r = 'list';
			}
			$template = $cat['template_list'] ? $cat['template_list'] : $template_r;


			$wprotypex=$_REQUEST['protype'];
			$protypex = F('Protype');
			$celebrity = explode(',', get_arrchildid(777, $protypex));

			if (in_array($wprotypex, $celebrity) && isMobile()) {
				$this->display($module . ':celebrity');
			}elseif($_REQUEST['pinpai']){
				$this->display($module . ':brand');
			} else {
				$this->display($module . ':' . $template);
			}


			/*if($brand){
				$this->display($module.':brand');
			}else{
				$this->display($module.':'.$template);
			}*/
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
		if(empty($cat['ishtml']))$this->dao->where("id=".$id)->setInc('hits'); //添加点击次数
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
		$seo_title = $data['title'].'-'.$cat['catname'];
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
		$data['content'] = doSortcode($data['content']);
		$this->assign('catid',$catid);
		$this->assign ($cat);
		$this->assign('bcid',$bcid);
		$this->assign ($data);
		$this->display($module.':'.$template); 
    }

	public function hits()
	{
		$module = $module ? $module : MODULE_NAME;
		$id = $id ? $id : intval($_REQUEST['id']);
		$this->dao= M($module);
		$this->dao->where("id=".$id)->setInc('hits');
		if($module=='Download'){
			$r = $this->dao->find($id);
			echo '$("#hits").html('.$r['hits'].');$("#downs").html('.$r['downs'].');';
		}else{
			$hits = $this->dao->where("id=".$id)->getField('hits');
			echo '$("#hits").html('.$hits.');';
		}
		exit;
	}
	public function verify()
    {
		header('Content-type: image/jpeg');
        $type	 =	 isset($_GET['type'])? get_safe_replace($_GET['type']):'jpeg';
        import("@.ORG.Image");
        Image::buildImageVerify(4,1,$type);
    }
}
?>