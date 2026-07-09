<?php
if(!defined("ThinkPHP")) exit("Access Denied");
class UserAction extends BaseAction{

    function _initialize()
    {
        parent::_initialize();

                echo "会員マイページ";
        exit;

        // 登录验证
        if (!$this->_userid) {
            redirect(U('Account/login'));
            exit;
        }

        $this->dao = M('User');

        $user = $this->dao->find($this->_userid);

        if(!$user){
            cookie('user_id', null);
            cookie('username', null);
            redirect(U('Account/login'));
            exit;
        }

        if(!$user['usersn']){
            $data['usersn']=setUserNo($this->_userid);
            $this->dao->where("id=".$this->_userid)->save($data);
            $user['usersn']=$data['usersn'];
        }

        $this->assign('user',$user);
        $this->assign('page_user_title','会員マイページ');
    }
    public function index(){
        echo "会員マイページ";
        exit;
        $this->display('User:index');
    }

}
?>