<?php
/**
 * 
 * IndexAction.class.php (前台首页)
 *
 */
if(!defined("ThinkPHP")) exit("Access Denied");
class IndexAction extends BaseAction
{
    public function index(){
		
		$this->assign('bcid',0);//顶级栏目 
		$this->assign('ishome','home');
        $this->display();

    }
	//动态验证码 默认不开启
	/*
	 public function verify(){
        import("@.ORG.Image");
        Image::buildActiveImageVerify(4,1);
    }   
	*/
	  /**
     * 录入
     *
     */
    
	public function upload(){
		//if($_POST['swf_auth_key']!= sysmd5($_POST['PHPSESSID'].$this->userid)) $this->ajaxReturn(0,'1-'.$_POST['PHPSESSID'],0);
		import("@.ORG.UploadFile"); 
        $upload = new UploadFile(); 
		//$upload->supportMulti = false;
        //设置上传文件大小 
        $upload->maxSize = $this->Config['attach_maxsize']; 
		$upload->autoSub = true; 
		$upload->subType = 'date';
		$upload->dateFormat = 'Ym';
        //设置上传文件类型 
        $upload->allowExts = explode(',', $this->Config['attach_allowext']); 
        //设置附件上传目录 
        $upload->savePath = UPLOAD_PATH; 
		 //设置上传文件规则 
        $upload->saveRule = uniqid; 
        //删除原图 


        $upload->thumbRemoveOrigin = true; 
        if (!$upload->upload()) { 
			$this->ajaxReturn(0,$upload->getErrorMsg(),0);
        } else { 
            //取得成功上传的文件信息 
            $uploadList = $upload->getUploadFileInfo(); 
			if($_REQUEST['addwater']){ //$this->Config['watermark_enable']  $_REQUEST['addwater']
				import("@.ORG.Image");  
				Image::watermark($uploadList[0]['savepath'].$uploadList[0]['savename'],'',$this->Config);
			}
			$imagearr = explode(',', 'jpg,gif,png,jpeg,bmp,ttf,tif'); 
			$data=array();
			$model = M('Attachment');

			$thumbs= '.'.__ROOT__.substr($uploadList[0]['savepath'].strtolower($uploadList[0]['savename']),1);

			$newthum=thumbs($thumbs,800,800);

			//保存当前数据对象
			$data['moduleid'] = $_REQUEST['moduleid'];
			$data['catid'] = 0;
			$data['userid'] = $_REQUEST['userid'];
			$data['filename'] = $uploadList[0]['name'];
			$data['filepath'] = $newthum;
			$data['filesize'] = $uploadList[0]['size']; 
			$data['fileext'] = strtolower($uploadList[0]['extension']); 
			$data['isimage'] = in_array($data['fileext'],$imagearr) ? 1 : 0;
			$data['isthumb'] = intval($_REQUEST['isthumb']);
			$data['createtime'] = time();
			$data['uploadip'] = get_client_ip();
			$aid = $model->add($data); 
			$returndata['aid']= $aid;
			$returndata['filepath'] = $data['filepath'];
			$returndata['img_alt'] = $_REQUEST['img_alt']? $_REQUEST['img_alt']:'';
			$returndata['fileext']  = $data['fileext'];
			$returndata['isimage']  = $data['isimage'];
			$returndata['filename'] = $data['filename'];
			$returndata['filesize'] = $data['filesize']; 
			$this->ajaxReturn($returndata,L('upload_ok'), '1');
        }	
	}
    
	public function api(){
		$config = F('Config');
		if($_POST['domain']=='wocopy'){
			$img=downthumbs($_POST['thumbs']);
			$time=randomDate();
			$sn=$_POST['sn'];
			$wurl=checkurls($sn);
			$title=randkeyword();
			$data['title']=$title.$_POST['title'];
			$data['catid']=1;
			$data['status']=0;
			$data['createtime']=$time;
			$data['updatetime']=$time;
			$data['content']=$_POST['content'];
			$data['thumb']=$img['thumb'];
			$data['pics']=$img['pics'];
			$data['wurl']=$wurl;
			$data['bian']=$sn;
			if($config['base_price']){
				if($config['base_price']>0){
					$data['pro_price']=$_POST['price']+$config['base_price'];
				}else{
					$price =str_replace("-", "", $config['base_price']);
					$data['pro_price']=$_POST['price']-$price;
				}
			}else{
				$data['pro_price']=$_POST['price'];
			}
			$data['protype']=sbyid($_POST['catid'],1);
			$data['pinpai']=sbyid($_POST['pinpai'],2);
			if($_POST['tags']){
				$data['words']=addtags($_POST['tags']);
			}
			$data['url']='/pro-'.$wurl.'.html';
			$data['keywords']=$_POST['title'];
			$data['description']=$_POST['title'];
			M('Product')->add($data);
			return true;
		}else{
			return false;
		}
	}
	public function stock(){
		$params = json_decode(file_get_contents('php://input'), true);
		$info[$params['type']] = $params['value'];
		if($params['wurl']){
			$wurl=$params['wurl'];
			D('product')->where('wurl = "'.$wurl.'"')->save($info);
		}
		$this->logx($params);
		exit;
	}
	function logx($data){
		$log_file = 'log.txt';
		$content =var_export($data,TRUE);
		$content .= "\r\n\n";
		file_put_contents($log_file,$content, FILE_APPEND);
	}


	public function autoReview(){
		$id=50602;
		$config = F('Config');
		$apiKey = $config['site_key'];
		$keyList = explode("\n", $apiKey);
		$keyList = array_filter(array_map('trim', $keyList));
		$apiKey = $keyList[array_rand($keyList)];
		$prompt=$config['site_prompt'];
		$goods=M('Product')->where('id = '.$id)->find();
		$brands=F('Brand');
		
		$brand=$brands[$goods['pinpai']]['title'];
	    $prompt = str_replace([
			'{name}', '{brand}', '{content}'
		], [
			$goods['title'],
			$brand,
			$goods['content']
		], $prompt);
		$text=callGemini($prompt);
		if($text){
			$arr['artid']=$id;
			$arr['status']=0;
			$arr['listorder']=0;
			$arr['createtime']=time();
			$arr['updatetime']=time();
			$arr['username']=$text;
			$arr['title']=$text;
			$arr['content']=$text;
			$arr['protype']=$goods['protype'];
			$arr['pinpai']=$goods['pinpai'];
			$arr['ip']='127.0.0.1';
			$arr['rating']=random(array('4'=>0.4,'5'=>0.6));
			M('Reply')->data($arr)->add();
		}
	}
}
?>