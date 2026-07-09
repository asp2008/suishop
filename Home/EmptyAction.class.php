<?php
/**
 * 
 * Empty (空模块)
 *
 */
if(!defined("ThinkPHP")) exit("Access Denied");
class EmptyAction extends Action
{	
	public $category;
	public function _empty()
	{
		//空操作 空模块
		if(MODULE_NAME!='Urlrule'){
			$Mod = F('Mod');			
			if(!$Mod[MODULE_NAME]){ 
				throw_exception('404');
			}
		}
		$this->category=F('Category');
		$a=ACTION_NAME;
		$id =  intval($_REQUEST['id']);
		$catid = intval($_REQUEST['catid']);
		if(!$catid){
			if($_REQUEST['brand']){
				if(!$_REQUEST['catdir']){
					$catid=1;
				}
			$_GET['brand']=$_REQUEST['brand'];
			$_GET['pinpai']=$_REQUEST['pinpai']=MD('Brand','bdurl="'.$_REQUEST['brand'].'"','id');
			}
		}
		
		$brand=MD('Brand','bdurl="'.$_REQUEST['brand'].'"','id');
		
		// 不存在的大分类
		if($_REQUEST['brand']){
			if($brand == null){
				header("HTTP/1.0 404 Not Found"); 
				throw_exception('404');
			}
		}
		
		$moduleid =  intval($_REQUEST['moduleid']);
		if(MODULE_NAME=='Urlrule'){
			if(APP_LANG){
				$l =get_safe_replace($_REQUEST['l']);
				$lang= $l ? '_'.$l : '_'.C('DEFAULT_LANG');
			}
			$catdir =get_safe_replace($_REQUEST['catdir']);
			if($catdir){
				$Cat = F('Cat'.$lang);
				$catid = $catid ? $catid : $Cat[$catdir];
				if($_REQUEST['wurl']){
					$id=MD($this->category[$catid]['module'],'wurl="'.$_REQUEST['wurl'].'"','id');
					
					// 不存在的商品
					if(!$id){
						header("HTTP/1.0 404 Not Found"); 
						throw_exception('404');
					}
					
					if(!$id){
						$id=MD($this->category[$catid]['module'],'title="'.$_REQUEST['wurl'].'"','id');
					}
					if(!$id){
						$id=$_REQUEST['wurl'];
					}
				}
				if(!$catid){
					$catid=1;
					$_REQUEST['protype']=$_GET['protype']=MD('Protype','catdir="'.$catdir.'"','id');
					
					// 不存在的子分类
					if(!$_REQUEST['protype']){
						header("HTTP/1.0 404 Not Found"); 
						throw_exception('404');
					}
					// var_dump($_REQUEST);
				}
				unset($Cat);
			}
			if($_REQUEST['module']){
				$m=get_safe_replace($_REQUEST['module']);						
			}elseif($moduleid){
				$Module =F('Module');
				$m=$Module[$moduleid]['module'];
				unset($Module);
			}elseif($catid){
				$Category = F('Category'.$lang);
				$m=$Category[$catid]['module'];
				unset($Category);
			}else{
				throw_exception('404');
			}
			if($a=='index') $id=$catid;
		}else{				
			if(empty($id)){
				$Cat = F('Cat'.$lang);
				$id = $Cat[$id];
				unset($Cat);
			}
			$m=MODULE_NAME;			
		}
		$urlpath=str_replace('\\', '/', dirname(__FILE__));
		if(file_exists($urlpath.'/'.$m.'Action.class.php')){
			$urlaction=$m.'Action';
		  	 $bae=new $urlaction();
			   if(!method_exists($bae,$a)){
			   	import('@.Action.Base');
				$bae=new BaseAction();
				if(!method_exists($bae,$a)){
					throw_exception('404');
				}
			   }
		}else{
			import('@.Action.Base');
			$bae=new BaseAction();
			if(!method_exists($bae,$a)){
				throw_exception('404');
			}
		}
		$_REQUEST['id']=$id;
		$bae->$a($id,$m);
	 
	}
}
?>