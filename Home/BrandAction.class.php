<?php
/**
 * 
 * FeedbackAction.class.php (询价模块)
 */
if(!defined("ThinkPHP")) exit("Access Denied");
class BrandAction extends BaseAction{

	function _initialize()
    {	
		parent::_initialize();
		$this->dao = M('Brand');
    }
	public function index(){
	   $lists=M('Brand')->where('status = 1')->order('listorder asc,id desc')
	   ->limit(1000)->select();
	   $config = F('Config');
	   $this->assign('mseo_title',$config['site_brand_title']);
	   $this->assign('mseo_keywords',$config['site_brand_key']);
	   $this->assign('mseo_description',$config['site_brand_desc']);
	   $this->assign ( 'list', $lists);
			   $this->display('Brand:list');
   }
   
	
}
?>