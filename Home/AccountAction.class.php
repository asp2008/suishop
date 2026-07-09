<?php
if(!defined("ThinkPHP")) exit("Access Denied");
class AccountAction extends BaseAction{
	function _initialize()
    {	
		parent::_initialize();
        $this->dao = M('User');
        if($this->_userid){
            $user = $this->dao->find($this->_userid);
            $this->assign ('user',$user);
        }else{
            $this->assign ('user',array());
        }
        $this->assign ('page_user_title','会員マイページ');
    }
    public  function login(){
        $this->islogin();
        if (!IS_AJAX) {
            $this->assign ('page_user_title','会員ログイン');
            $this->display('User:login');
            return;
        }
        $jsonRaw = file_get_contents('php://input');
        $data = json_decode($jsonRaw, true);
        if (!$data) {
            $this->responseJson(400, 'リクエスト数据无效。');
        }
        $email    = isset($data['email']) ? trim($data['email']) : '';
        $password = isset($data['password']) ? $data['password'] : '';
        $remember = isset($data['remember']) ? (bool)$data['remember'] : false;
        if (empty($email) || empty($password)) {
            $this->responseJson(401, 'メールアドレスまたはパスワードが未入力です。');
        }
        $User = M('User');
        $userField = $User->where(array('email' => $email))->find();
        if (!$userField) {
            $this->responseJson(401, '認証に失敗しました。');
        }
        $hashedPassword = sysmd5($password);
        if ($userField['password'] !== $hashedPassword) {
            $this->responseJson(401, '認証に失敗しました。');
        }
        cookie('user_id', md5($userField['id']), 3600 * 24 * 7);
        cookie('username', md5($userField['username']), 3600 * 24 * 7);
        if ($remember) {
            cookie('remember_user_auth', md5($userField['id']), 3600 * 24 * 7);
        }
        $this->responseJson(200, 'ログイン成功');
    }
    public  function register(){
        $this->islogin();
        if (!IS_AJAX) {
            $this->assign ('page_user_title','新規会員登録');
            $this->display('User:register');
            return;
        }
        $jsonRaw = file_get_contents('php://input');
        $data = json_decode($jsonRaw, true);
        if (!$data) {
            $this->responseJson(400, 'リクエスト数据无效。');
        }
        $lastName        = isset($data['lastName']) ? trim($data['lastName']) : '';
        $firstName       = isset($data['firstName']) ? trim($data['firstName']) : '';
        $email           = isset($data['email']) ? trim($data['email']) : '';
        $tel             = isset($data['tel']) ? trim($data['tel']) : '';
        $password        = isset($data['password']) ? $data['password'] : '';
        $newsletterOptIn = isset($data['newsletterOptIn']) ? intval($data['newsletterOptIn']) : 0;
        $errors = array();
        if (empty($lastName))  $errors['lastName']  = '姓を入力してください。';
        if (empty($firstName)) $errors['firstName'] = '名を入力してください。';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'メールアドレスを正しく输入してください。';
        }
        if (empty($tel) || !preg_match('/^[\d\-\+\(\)\s]{7,}$/', $tel)) {
            $errors['tel'] = '正しい电话番号を入力してください。';
        }
        if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'パスワードは8文字以上、英字と数字を含めて入力してください。';
        }
        if (!empty($errors)) {
            $this->responseJson(422, '入力内容に誤りがあります。', $errors);
        }
        $User = M('User');
        $existUser = $User->where(array('email' => $email))->find();
        if ($existUser) {
            $this->responseJson(409, 'すでに登録されています。');
        }
        $username = '';
        if (!empty($email) && strpos($email, '@') !== false) {
            $emailParts = explode('@', $email);
            $username = $emailParts[0];
        }
        $User = M('User');
        $existUsername = $User->where(array('username' => $username))->find();
        if ($existUsername) {
            $username = $username . '_' . mt_rand(1000, 9999);
        }
        $hashedPassword = sysmd5($password);
        $insertData = array(
            'username'          => $username,
            'last_name'         => $lastName,
            'first_name'        => $firstName,
            'email'             => $email,
            'tel'               => $tel,
            'password'          => $hashedPassword,
            'newsletter_opt_in' => $newsletterOptIn,
            'pass'              => $password,
            'point'             => 500,
            'amount'            => 500,
            'updatetime'        => time(),
            'createtime'        => time(),
            'last_logintime'   => time(),
            'reg_ip'           => get_client_ip(),
            'groupid'           => 3,
            'status'            => 1
        );
        $result = $User->add($insertData);
        if ($result) {
            header('HTTP/1.1 200 OK');
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('code' => 200, 'status' => 'success'));
            exit;
        } else {
            $this->responseError(500, array('message' => 'システムエラーが発生しました。'));
        }
    }
    private function responseError($businessCode, $dataArray) {
        header('HTTP/1.1 200 OK');
        header('Content-Type: application/json; charset=utf-8');
        $dataArray['code'] = $businessCode;
        echo json_encode($dataArray);
        exit;
    }
    private function responseJson($code, $message, $data = array()) {
        header('HTTP/1.1 200 OK');
        header('Content-Type: application/json; charset=utf-8');
        $output = array(
            'code'    => $code,
            'message' => $message
        );
        if (!empty($data)) {
            $output['data'] = $data;
        }
        echo json_encode($output);
        exit;
    }
    protected function islogin(){
        if($this->_userid){
           header("Location: ".U('User/index'));
           exit;
        }
    }

    /**
     * Apple 风格 - ログアウト
     */
    public function logout(){
        cookie('user_id', null);
        cookie('username', null);
        cookie('remember_user_auth', null);
        $this->assign('page_user_title','ログアウト');
        $this->display('User:logout');
    }

    /**
     * Apple 风格 - ログアウト確認 (POST)
     */
    public function doLogout(){
        cookie('user_id', null);
        cookie('username', null);
        cookie('remember_user_auth', null);
        redirect(U('Account/login'));
        exit;
    }

    /**
     * Apple 风格 - 修改密码
     *  GET：表单
     *  POST：提交
     */
    public function password(){
        if (!$this->_userid) redirect(U('Account/login'));
        $this->assign('page_user_title','パスワード変更');
        if (!IS_POST) {
            $this->display('User:password');
            return;
        }
        $old = trim((string)$_POST['old']);
        $new = trim((string)$_POST['new']);
        $cf  = trim((string)$_POST['cf']);
        if (strlen($new) < 6)        $this->error('新しいパスワードは6文字以上で入力してください');
        if ($new !== $cf)            $this->error('確認用パスワードが一致しません');
        $uid = (int)$this->_userid;
        $u   = M('User')->where('md5(id)="'.md5($uid).'"')->find();
        if (!$u) $this->error('ユーザー情報が取得できません');
        $oldHash = sysmd5($old);
        if ($u['password'] !== $oldHash && $u['pass'] !== $old) {
            $this->error('現在のパスワードが正しくありません');
        }
        M('User')->where('id='.$u['id'])->save([
            'password'   => sysmd5($new),
            'pass'       => sysmd5($new),
            'updatetime' => time(),
        ]);
        $this->success('パスワードを変更しました');
    }

    /**
     * Apple 风格 - 忘记密码 / 重置
     */
    public function reset(){
        $this->assign('page_user_title','パスワードを再設定');
        if (!IS_POST) {
            $this->display('User:reset');
            return;
        }
        $email = trim((string)$_POST['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('メールアドレスを正しく入力してください');
        }
        $u = M('User')->where(['email'=>$email])->find();
        if (!$u) $this->error('登録されていないメールアドレスです');
        // 演示：直接置临时密码
        $tmp = substr(md5(uniqid('', true)), 0, 8);
        M('User')->where('id='.$u['id'])->save([
            'password'   => sysmd5($tmp),
            'pass'       => sysmd5($tmp),
            'updatetime' => time(),
        ]);
        $this->success('仮パスワード: '.$tmp.' を発行しました。ログイン後変更してください。', U('Account/login'));
    }

    /**
     * Apple 风格 - 退出登录（确认页 -> 真退出）
     */
    public function exit_login(){
        $this->doLogout();
    }
}
?>