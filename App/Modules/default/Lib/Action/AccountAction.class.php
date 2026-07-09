<?php
/**
 * 会员中心 / 订单 / 地址 / 收藏 / 站内信
 *  Action 名：login / register / logout / dashboard / orders / order_detail / address / favorite / point / message / profile / password
 */
class AccountAction extends CommonAction
{
    /* ============================================================
     * 登录 / 注册 / 登出
     * ============================================================ */

    /**
     * 登录
     */
    public function login()
    {
        if ($this->uid) {
            redirect(U('Account/dashboard'));
        }
        if (IS_POST) {
            $username = trim(I('username'));
            $password = trim(I('password'));
            if ($username === '' || $password === '') $this->error('账号或密码不能为空');

            $row = M('User')->where(array('username' => $username))->find();
            if (!$row) {
                // 也支持邮箱登录
                $row = M('User')->where(array('email' => $username))->find();
            }
            if (!$row) $this->error('账号不存在');

            if ((int)$row['status'] === 1) $this->error('账号已停用，请联系客服');

            // 密码校验（兼容 md5 与明文历史数据）
            $ok = ($row['password'] === md5($password)) || ($row['password'] === $password) || ($row['pass'] === md5($password));
            if (!$ok) $this->error('密码错误');

            // 更新登录信息
            M('User')->where(array('id' => $row['id']))->save(array(
                'login_count'     => array('exp', 'login_count+1'),
                'last_logintime'  => time(),
                'last_ip'         => get_client_ip(),
            ));

            // 写入 session
            unset($row['password'], $row['pass']);
            session('user', $row);

            // 合并未登录购物车
            $this->_mergeCart($row['id']);

            $ref = I('ref');
            $ref = $ref ? base64_decode($ref) : U('Account/dashboard');
            redirect($ref);
        }
        $this->display('login');
    }

    /**
     * 注册
     */
    public function register()
    {
        if ($this->uid) redirect(U('Account/dashboard'));
        if (IS_POST) {
            $username = trim(I('username'));
            $password = trim(I('password'));
            $email    = trim(I('email'));
            $mobile   = trim(I('mobile'));

            if ($username === '' || $password === '') $this->error('请填写用户名与密码');
            if (strlen($password) < 6) $this->error('密码至少 6 位');
            if (M('User')->where(array('username' => $username))->find()) $this->error('该用户名已被使用');
            if ($email && M('User')->where(array('email' => $email))->find()) $this->error('该邮箱已注册');

            $now = time();
            $data = array(
                'usersn'      => 'U'.date('Ymd').mt_rand(1000, 9999),
                'username'    => $username,
                'password'    => md5($password),
                'pass'        => md5($password),
                'email'       => $email,
                'mobile'      => $mobile,
                'createtime'  => $now,
                'updatetime'  => $now,
                'last_logintime' => $now,
                'reg_ip'      => get_client_ip(),
                'status'      => 0,
                'point'       => 500, // 注册赠送
                'amount'      => 0.00,
            );
            $uid = M('User')->add($data);
            if (!$uid) $this->error('注册失败，请重试');
            $data['id'] = $uid;
            unset($data['password'], $data['pass']);
            session('user', $data);

            $this->_mergeCart($uid);

            $this->success('注册成功！为您准备了 500 积分', U('Account/dashboard'));
        }
        $this->display('register');
    }

    /**
     * 退出
     */
    public function logout()
    {
        session('user', null);
        cookie('cart_sid', null);
        redirect(U('My/index'));
    }

    /* ============================================================
     * 会员中心 Dashboard
     * ============================================================ */

    public function dashboard()
    {
        require_login();

        // 概览：积分/订单数/收藏
        $orderCount = (int)M('Order')->where(array('userid' => $this->uid))->count();
        $favCount   = (int)M('UserCollect')->where(array('userid' => $this->uid))->count();
        $point      = (int)$this->member['point'];

        // 最近订单
        $orders = M('Order')->where(array('userid' => $this->uid))
            ->order('id DESC')->limit(3)->select();
        foreach ($orders as &$o) {
            $o['status_label'] = $this->_orderStatus($o);
            $thumb = M('OrderData')->where(array('order_id' => $o['id']))->getField('product_thumb');
            $o['thumb'] = $thumb ?: '__IMG__/placeholder.svg';
        }

        // 推荐商品
        $recom = M('Product')->where(array('status' => 1))->order('id DESC')->limit(4)->select();
        foreach ($recom as &$r) {
            $r['price_num'] = (float)$r['pro_price'];
            $r['thumb_url'] = upload_url($r['thumb']);
            $r['url']       = U('My/detail', array('id' => $r['id']));
        }

        // 会员等级
        $level = M('UserLevel')->where('amount <= '.(float)$this->member['total_amount'])
            ->order('amount DESC')->find();

        $this->assign(array(
            'page_title' => 'マイページ | '.$this->site['name'],
            'orderCount' => $orderCount,
            'favCount'   => $favCount,
            'point'      => $point,
            'orders'     => $orders,
            'recom'      => $recom,
            'level'      => $level,
        ));
        $this->display('dashboard');
    }

    /* ============================================================
     * 订单
     * ============================================================ */

    /**
     * 订单列表
     */
    public function orders()
    {
        require_login();
        $status = I('status');
        $where = array('userid' => $this->uid);
        if ($status !== '' && $status !== null) {
            $where['status'] = (int)$status;
        }
        $list = M('Order')->where($where)->order('id DESC')->limit(20)->select();
        foreach ($list as &$o) {
            $o['status_label'] = $this->_orderStatus($o);
            $o['items'] = M('OrderData')->where(array('order_id' => $o['id']))->select();
        }

        // 状态计数
        $countMap = array(
            'all'    => (int)M('Order')->where(array('userid' => $this->uid))->count(),
            'unpaid' => (int)M('Order')->where(array('userid' => $this->uid, 'status' => 0))->count(),
            'paid'   => (int)M('Order')->where(array('userid' => $this->uid, 'status' => 1))->count(),
            'ship'   => (int)M('Order')->where(array('userid' => $this->uid, 'shipping_status' => 1))->count(),
            'done'   => (int)M('Order')->where(array('userid' => $this->uid, 'status' => 2))->count(),
        );

        $this->assign(array(
            'page_title' => '注文履歴 | '.$this->site['name'],
            'list'       => $list,
            'countMap'   => $countMap,
            'status'     => $status,
        ));
        $this->display('orders');
    }

    /**
     * 订单详情
     */
    public function order_detail()
    {
        require_login();
        $id = (int)I('id');
        $o  = M('Order')->where(array('id' => $id, 'userid' => $this->uid))->find();
        if (!$o) $this->display('Public:404'); exit;
        $o['status_label'] = $this->_orderStatus($o);
        $items = M('OrderData')->where(array('order_id' => $id))->select();
        $this->assign(array(
            'page_title' => '注文詳細 '.$o['sn'].' | '.$this->site['name'],
            'order'      => $o,
            'items'      => $items,
        ));
        $this->display('order_detail');
    }

    /**
     * 取消订单（仅 status=0 待付款）
     */
    public function order_cancel()
    {
        require_login();
        $id = (int)I('id');
        $o  = M('Order')->where(array('id' => $id, 'userid' => $this->uid))->find();
        if (!$o) json_out(array('code' => 404, 'msg' => '订单不存在'));
        if ((int)$o['status'] !== 0) json_out(array('code' => 1, 'msg' => '该订单不允许取消'));

        M('Order')->where(array('id' => $id))->save(array('status' => 4, 'confirm_time' => time()));
        json_out(array('code' => 0, 'msg' => '订单已取消'));
    }

    /**
     * 确认收货
     */
    public function order_confirm()
    {
        require_login();
        $id = (int)I('id');
        $o  = M('Order')->where(array('id' => $id, 'userid' => $this->uid))->find();
        if (!$o) json_out(array('code' => 404, 'msg' => '订单不存在'));

        M('Order')->where(array('id' => $id))->save(array(
            'status'       => 2,
            'shipping_status' => 2,
            'confirm_time' => time(),
        ));
        json_out(array('code' => 0, 'msg' => '已确认收货，感谢您的购买'));
    }

    /* ============================================================
     * 收货地址
     * ============================================================ */

    public function address()
    {
        require_login();
        $list = M('UserAddress')->where(array('userid' => $this->uid))->order('isdefault DESC, id DESC')->select();
        // 简单拼接省市区（div_area）
        foreach ($list as &$a) {
            $a['region'] = $this->_region($a['province'], $a['city'], $a['area']);
        }
        $this->assign(array(
            'page_title' => 'お届け先住所 | '.$this->site['name'],
            'list'       => $list,
        ));
        $this->display('address');
    }

    public function address_save()
    {
        require_login();
        $id = (int)I('id');
        $data = array(
            'userid'    => $this->uid,
            'consignee1' => I('consignee'),
            'tel1'       => I('tel'),
            'mobile'     => I('mobile'),
            'email'      => I('email'),
            'province'   => (int)I('province'),
            'city'       => (int)I('city'),
            'area'       => (int)I('area'),
            'address1'   => I('address1'),
            'address2'   => I('address2'),
            'address3'   => I('address3'),
            'zip'        => I('zip'),
            'title'      => I('title', '家'),
            'isdefault'  => (int)I('isdefault', 0),
            'updatetime' => time(),
        );
        if ($data['isdefault']) {
            M('UserAddress')->where(array('userid' => $this->uid))->save(array('isdefault' => 0));
        }
        if ($id) {
            M('UserAddress')->where(array('id' => $id, 'userid' => $this->uid))->save($data);
        } else {
            $data['isdefault'] = $data['isdefault'] ?: 1;
            $id = M('UserAddress')->add($data);
        }
        $this->success('保存成功', U('Account/address'));
    }

    public function address_del()
    {
        require_login();
        $id = (int)I('id');
        M('UserAddress')->where(array('id' => $id, 'userid' => $this->uid))->delete();
        $this->success('删除成功', U('Account/address'));
    }

    public function address_set_default()
    {
        require_login();
        $id = (int)I('id');
        M('UserAddress')->where(array('userid' => $this->uid))->save(array('isdefault' => 0));
        M('UserAddress')->where(array('id' => $id, 'userid' => $this->uid))->save(array('isdefault' => 1));
        $this->success('已设为默认', U('Account/address'));
    }

    /**
     * 省市区
     */
    public function region()
    {
        $parent = (int)I('parent', 0);
        $list = M('Area')->where(array('parentid' => $parent))->order('listorder ASC')->select();
        json_out(array('code' => 0, 'data' => $list));
    }

    /* ============================================================
     * 收藏
     * ============================================================ */

    public function favorite()
    {
        require_login();
        $rows = M('UserCollect')->where(array('userid' => $this->uid))->order('time DESC')->select();
        $list = array();
        $Product = M('Product');
        foreach ($rows as $r) {
            $p = $Product->where(array('id' => $r['proid'], 'status' => 1))->find();
            if (!$p) continue;
            $p['price_num'] = (float)$p['pro_price'];
            $p['thumb_url'] = upload_url($p['thumb']);
            $p['url']       = U('My/detail', array('id' => $p['id']));
            $list[] = $p;
        }
        $this->assign(array(
            'page_title' => 'お気に入り | '.$this->site['name'],
            'list'       => $list,
        ));
        $this->display('favorite');
    }

    /* ============================================================
     * 积分
     * ============================================================ */

    public function point()
    {
        require_login();
        $log = M('UserSign')->where(array('userid' => $this->uid))->order('id DESC')->limit(30)->select();
        $this->assign(array(
            'page_title' => 'ポイント履歴 | '.$this->site['name'],
            'log'        => $log,
            'point'      => (int)$this->member['point'],
        ));
        $this->display('point');
    }

    /**
     * 签到 +10 积分（演示）
     */
    public function sign()
    {
        require_login();
        $today = date('Ymd');
        $has = M('UserSign')->where(array('userid' => $this->uid, 'addtime' => $today))->find();
        if ($has) json_out(array('code' => 1, 'msg' => '今日已签到'));
        M('UserSign')->add(array('userid' => $this->uid, 'addtime' => $today, 'point' => 10, 'createtime' => time()));
        M('User')->where(array('id' => $this->uid))->setInc('point', 10);
        json_out(array('code' => 0, 'msg' => '签到成功 +10 积分'));
    }

    /* ============================================================
     * 站内信
     * ============================================================ */

    public function message()
    {
        require_login();
        $list = M('Message')->where(array('member_id' => $this->uid))->order('updated_at DESC')->limit(20)->select();
        $this->assign(array(
            'page_title' => '站内信 | '.$this->site['name'],
            'list'       => $list,
        ));
        $this->display('message');
    }

    public function message_detail()
    {
        require_login();
        $id  = (int)I('id');
        $row = M('Message')->where(array('id' => $id, 'member_id' => $this->uid))->find();
        if (!$row) $this->display('Public:404'); exit;
        $detail = M('MessageDetail')->where(array('message_id' => $id))->order('id ASC')->select();
        $this->assign(array(
            'page_title' => $row['title'].' | '.$this->site['name'],
            'message'    => $row,
            'detail'     => $detail,
        ));
        $this->display('message_detail');
    }

    /* ============================================================
     * 资料 / 修改密码
     * ============================================================ */

    public function profile()
    {
        require_login();
        if (IS_POST) {
            $data = array(
                'realname'   => I('realname'),
                'email'      => I('email'),
                'mobile'     => I('mobile'),
                'sex'        => (int)I('sex', 0),
                'address'    => I('address'),
                'updatetime' => time(),
            );
            M('User')->where(array('id' => $this->uid))->save($data);
            $row = M('User')->find($this->uid);
            unset($row['password'], $row['pass']);
            session('user', $row);
            $this->success('保存成功');
        }
        $this->assign(array(
            'page_title' => '会員情報設定 | '.$this->site['name'],
        ));
        $this->display('profile');
    }

    public function password()
    {
        require_login();
        if (IS_POST) {
            $old = trim(I('old'));
            $new = trim(I('new'));
            $cf  = trim(I('cf'));
            if (strlen($new) < 6) $this->error('新密码至少 6 位');
            if ($new !== $cf) $this->error('两次新密码不一致');

            $u = M('User')->find($this->uid);
            $ok = ($u['password'] === md5($old)) || ($u['password'] === $old) || ($u['pass'] === md5($old));
            if (!$ok) $this->error('原密码不正确');
            M('User')->where(array('id' => $this->uid))->save(array(
                'password'   => md5($new),
                'pass'       => md5($new),
                'updatetime' => time(),
            ));
            $this->success('密码修改成功');
        }
        $this->display('password');
    }

    /* ============================================================
     * 内部
     * ============================================================ */

    /**
     * 把未登录时的购物车合并到登录用户
     */
    private function _mergeCart($uid)
    {
        $sid = cookie('cart_sid');
        if (!$sid) return;
        M('Cart')->where(array('sessionid' => $sid, 'userid' => 0))
            ->save(array('userid' => $uid));
    }

    /**
     * 拼接省市区字符串
     */
    private function _region($provinceId, $cityId, $areaId)
    {
        $Area = M('Area');
        $str = '';
        if ($provinceId) $str .= $Area->where(array('id' => $provinceId))->getField('name');
        if ($cityId)     $str .= ' '.$Area->where(array('id' => $cityId))->getField('name');
        if ($areaId)     $str .= ' '.$Area->where(array('id' => $areaId))->getField('name');
        return $str;
    }

    /**
     * 订单状态（中文）
     * status: 0=待付款 1=已付款待发货 2=已完成 3=退款中 4=已取消
     * shipping_status: 0=未发货 1=已发货 2=已签收
     */
    private function _orderStatus($o)
    {
        $status = (int)$o['status'];
        $ship   = (int)$o['shipping_status'];
        $map = array(
            0 => array('text' => '待付款', 'cls' => 'unpaid'),
            1 => $ship === 1 ? array('text' => '配送中', 'cls' => 'ship') : array('text' => '待发货', 'cls' => 'paid'),
            2 => array('text' => '已完成', 'cls' => 'done'),
            3 => array('text' => '退款中', 'cls' => 'refund'),
            4 => array('text' => '已取消', 'cls' => 'cancel'),
        );
        return isset($map[$status]) ? $map[$status] : array('text' => '未知', 'cls' => '');
    }
}