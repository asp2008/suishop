<?php
/**
 *
 * MyAction.class.php (前台综合 - 会员中心 / 品牌 / 文章 / 收藏 / 消息 / 反馈 / 搜索 / 退出 等)
 *
 * 商品的列表/详情 统一由 ProductAction 提供；本类专注于"个人中心 + 内容展示"。
 *
 */
if(!defined("ThinkPHP")) exit("Access Denied");
class MyAction extends BaseAction
{
    /**
     * 进入 _initialize 前要求已登录
     */
    public function _initialize()
    {
        parent::_initialize();
        // MyAction 部分方法（dashboard/orders/favorite 等）需要登录，
        // 部分方法（search/brand/article/page/feedback）不要求登录
        $needLogin = array('dashboard','orders','favorite','favoriteAdd','favoriteDel',
                           'address','addressSave','addressDel',
                           'point','sign',
                           'message','messageDetail',
                           'profile','profileSave',
                           'inquiry');
        $act = strtolower(ACTION_NAME);
        if (in_array($act, $needLogin) && empty($this->_userid)) {
            redirect(U('Account/login'));
            exit;
        }
        // 注入常用变量
        $this->assign('page_user_title','会員マイページ');
    }

    /* ============================================================
     * 个人中心 Dashboard（首页 - 我的）
     * ============================================================ */

    public function dashboard(){
        $uid = (int)$this->_userid;
        $user = M('User')->where('md5(id)="'.md5($uid).'"')->find();

        // 概览
        $point = (int)$user['point'];
        $orderCount = (int)M('Order')->where('userid='.$uid)->count();
        $favCount   = (int)M('UserCollect')->where('userid='.$uid)->count();

        // 最近订单（3 条）
        $orders = M('Order')->where('userid='.$uid)->order('id DESC')->limit(3)->select();
        foreach ($orders as &$o) {
            $o['status_label'] = $this->_orderStatus($o);
            $thumb = M('OrderData')->where('order_id='.$o['id'])->getField('product_thumb');
            $o['thumb'] = $thumb ?: '__STYLE__/css/suni_logo-041.png';
        }

        // 推荐商品
        $recom = M('Product')->where('status=1')->order('id DESC')->limit(4)->select();
        foreach ($recom as &$r) {
            $r['price_num']  = (float)$r['pro_price'];
            $r['thumb_url']  = $r['thumb'] ?: '__STYLE__/css/suni_logo-041.png';
            $r['detail_url'] = U('Product/show', array('id'=>$r['id']));
        }

        // 会员等级
        $level = M('UserLevel')->where('amount <= '.(float)$user['total_amount'])->order('amount DESC')->find();

        $this->assign('user', $user);
        $this->assign('point', $point);
        $this->assign('orderCount', $orderCount);
        $this->assign('favCount', $favCount);
        $this->assign('orders', $orders);
        $this->assign('recom', $recom);
        $this->assign('level', $level);
        $this->assign('page_user_title','マイページ');
        $this->display('User:index');
    }

    public function index(){
        $this->dashboard();
    }

    /* ============================================================
     * 订单列表（简单代理；保留以兼容你现有链接）
     * ============================================================ */

    public function orders(){
        $this->display('User:orders');
    }

    public function order_detail(){
        $id = (int)$_REQUEST['id'];
        $uid = (int)$this->_userid;
        $o = M('Order')->where('id='.$id.' AND userid='.$uid)->find();
        if (!$o) $this->error('注文が存在しません');
        $o['status_label'] = $this->_orderStatus($o);
        $items = M('OrderData')->where('order_id='.$id)->select();
        $this->assign('order', $o);
        $this->assign('items', $items);
        $this->display('User:order_detail');
    }

    /* ============================================================
     * 收藏
     * ============================================================ */

    public function favorite(){
        $uid = (int)$this->_userid;
        $rows = M('UserCollect')->where('userid='.$uid)->order('time DESC')->select();
        $list = array();
        $Product = M('Product');
        foreach ($rows as $r) {
            $p = $Product->where('id='.(int)$r['proid'].' AND status=1')->find();
            if (!$p) continue;
            $p['price_num']  = (float)$p['pro_price'];
            $p['thumb_url']  = $p['thumb'] ?: '__STYLE__/css/suni_logo-041.png';
            $p['detail_url'] = U('Product/show', array('id'=>$p['id']));
            $list[] = $p;
        }
        $this->assign('list', $list);
        $this->display('User:favorite');
    }

    /**
     * 收藏添加 - Ajax
     *  GET ?id=
     */
    public function favoriteAdd(){
        $uid = (int)$this->_userid;
        $id  = (int)$_REQUEST['id'];
        if ($id <= 0) $this->ajaxReturn('', 'パラメータエラー', 0);
        $has = M('UserCollect')->where('userid='.$uid.' AND proid='.$id)->find();
        if ($has) $this->ajaxReturn('', 'すでにお気に入りに追加されています', 1);
        M('UserCollect')->add(array(
            'userid' => $uid,
            'proid'  => $id,
            'time'   => time(),
            'ip'     => get_client_ip(),
        ));
        $this->ajaxReturn('', 'お気に入りに追加しました', 1);
    }

    /**
     * 收藏删除 - Ajax
     *  GET ?id=
     */
    public function favoriteDel(){
        $uid = (int)$this->_userid;
        $id  = (int)$_REQUEST['id'];
        M('UserCollect')->where('userid='.$uid.' AND proid='.$id)->delete();
        $this->ajaxReturn('', 'お気に入りを解除しました', 1);
    }

    /* ============================================================
     * 收货地址
     * ============================================================ */

    public function address(){
        $uid = (int)$this->_userid;
        $list = M('UserAddress')->where('userid='.$uid)->order('isdefault DESC, id DESC')->select();
        $Area = M('Area');
        foreach ($list as &$a) {
            $a['region'] = $this->_region($Area, $a['province'], $a['city'], $a['area']);
        }
        $this->assign('list', $list);
        $this->display('User:address');
    }

    public function addressSave(){
        $uid = (int)$this->_userid;
        $id  = (int)$_POST['id'];
        $data = array(
            'userid'     => $uid,
            'consignee1' => trim((string)$_POST['consignee']),
            'tel1'       => trim((string)$_POST['tel']),
            'mobile'     => trim((string)$_POST['mobile']),
            'email'      => trim((string)$_POST['email']),
            'province'   => (int)$_POST['province'],
            'city'       => (int)$_POST['city'],
            'area'       => (int)$_POST['area'],
            'address1'   => trim((string)$_POST['address1']),
            'address2'   => trim((string)$_POST['address2']),
            'address3'   => trim((string)$_POST['address3']),
            'zip'        => trim((string)$_POST['zip']),
            'title'      => trim((string)$_POST['title']),
            'isdefault'  => (int)$_POST['isdefault'],
            'updatetime' => time(),
        );
        if ($data['isdefault']) {
            M('UserAddress')->where('userid='.$uid)->save(array('isdefault'=>0));
        }
        if ($id) {
            M('UserAddress')->where('id='.$id.' AND userid='.$uid)->save($data);
        } else {
            $data['isdefault'] = $data['isdefault'] ? 1 : 0;
            $id = M('UserAddress')->add($data);
        }
        $this->success('保存しました');
    }

    public function addressDel(){
        $uid = (int)$this->_userid;
        $id  = (int)$_REQUEST['id'];
        M('UserAddress')->where('id='.$id.' AND userid='.$uid)->delete();
        $this->success('削除しました');
    }

    /* ============================================================
     * 积分
     * ============================================================ */

    public function point(){
        $uid = (int)$this->_userid;
        $log = M('UserSign')->where('userid='.$uid)->order('id DESC')->limit(30)->select();
        $this->assign('log', $log);
        $this->assign('point', (int)M('User')->where('md5(id)="'.md5($uid).'"')->getField('point'));
        $this->display('User:point');
    }

    /**
     * 每日签到
     */
    public function sign(){
        $uid = (int)$this->_userid;
        $today = date('Ymd');
        $has = M('UserSign')->where('userid='.$uid.' AND addtime="'.$today.'"')->find();
        if ($has) $this->ajaxReturn('', '今日すでに签到しました', 0);
        M('UserSign')->add(array('userid'=>$uid, 'addtime'=>$today, 'point'=>10, 'createtime'=>time()));
        M('User')->where('md5(id)="'.md5($uid).'"')->setInc('point', 10);
        $this->ajaxReturn('', '+10 ポイント', 1);
    }

    /* ============================================================
     * 站内信 / 消息中心
     * ============================================================ */

    public function message(){
        $uid = (int)$this->_userid;
        $list = M('Message')->where('member_id='.$uid)->order('updated_at DESC')->limit(20)->select();
        $this->assign('list', $list);
        $this->display('User:message');
    }

    public function messageDetail(){
        $uid = (int)$this->_userid;
        $id  = (int)$_REQUEST['id'];
        $row = M('Message')->where('id='.$id.' AND member_id='.$uid)->find();
        if (!$row) $this->error('メッセージが存在しません');
        $detail = M('MessageDetail')->where('message_id='.$id)->order('id ASC')->select();
        // 标为已读
        M('Message')->where('id='.$id)->save(array('member_unread'=>0));
        $this->assign('message', $row);
        $this->assign('detail', $detail);
        $this->display('User:message_detail');
    }

    /* ============================================================
     * 资料 / 修改
     * ============================================================ */

    public function profile(){
        $uid = (int)$this->_userid;
        $u   = M('User')->where('md5(id)="'.md5($uid).'"')->find();
        $this->assign('user', $u);
        $this->display('User:profile');
    }

    public function profileSave(){
        $uid = (int)$this->_userid;
        $u   = M('User')->where('md5(id)="'.md5($uid).'"')->find();
        if (!$u) $this->error('ユーザーが存在しません');
        $data = array(
            'realname'   => trim((string)$_POST['realname']),
            'email'      => trim((string)$_POST['email']),
            'mobile'     => trim((string)$_POST['mobile']),
            'sex'        => (int)$_POST['sex'],
            'address'    => trim((string)$_POST['address']),
            'updatetime' => time(),
        );
        M('User')->where('id='.$u['id'])->save($data);
        $this->success('保存しました');
    }

    /* ============================================================
     * 客服咨询入口（落地到 mypage-inquiry）
     * ============================================================ */

    public function inquiry(){
        $uid = (int)$this->_userid;
        $orders = M('Order')->where('userid='.$uid)->order('id DESC')->limit(5)->select();
        $this->assign('orders', $orders);
        $this->display('User:qa');
    }

    /* ============================================================
     * 品牌（产品跳转兼容）
     * ============================================================ */

    public function brand(){
        $this->display('Brand:list');
    }

    public function brandShow(){
        $this->display('Brand:show');
    }

    /* ============================================================
     * 文章 / 单页 / 搜索 / 反馈 / 退出（公开）
     * ============================================================ */

    public function article(){
        $this->display('News:list');
    }

    public function articleShow(){
        $this->display('News:show');
    }

    public function page(){
        $this->display('Page:index');
    }

    public function search(){
        $q = trim((string)$_REQUEST['q']);
        $this->assign('q', $q);
        $this->display('Search:index');
    }

    public function feedback(){
        $this->display('Feedback:index');
    }

    public function faq(){
        $this->display('Page:qas');
    }

    public function links(){
        $this->display('Page:hot');
    }

    public function contact(){
        $this->display('Page:contact');
    }

    public function sitemap(){
        $this->display('Page:sitemap');
    }

    /**
     * 退出登录（GET 直接清 cookie）
     */
    public function logout(){
        cookie('user_id', null);
        cookie('username', null);
        cookie('remember_user_auth', null);
        redirect(U('Account/login'));
        exit;
    }

    /* ============================================================
     * 内部 helpers
     * ============================================================ */

    private function _region($Area, $p, $c, $a){
        $str = '';
        if ($p) $str .= $Area->where('id='.$p)->getField('name');
        if ($c) $str .= ' '.$Area->where('id='.$c)->getField('name');
        if ($a) $str .= ' '.$Area->where('id='.$a)->getField('name');
        return $str;
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
