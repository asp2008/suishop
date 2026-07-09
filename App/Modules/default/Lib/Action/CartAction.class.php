<?php
/**
 * 购物车 / 结算 / 订单提交 / 订单完成 / 支付入口（演示）
 *  Action 名：index / add / update / remove / coupon / checkout / submit / done / pay
 */
class CartAction extends CommonAction
{
    /* ============================================================
     * 购物车列表
     * ============================================================ */

    public function index()
    {
        $items = $this->_loadCart();
        $summary = $this->_summary($items);

        $this->assign(array(
            'page_title' => 'ショッピングカート | '.$this->site['name'],
            'items'      => $items,
            'summary'    => $summary,
            'empty'      => empty($items),
        ));
        $this->display('index');
    }

    /* ============================================================
     * 加入购物车（GET / POST）
     *  - POST：常规表单
     *  - GET ?do=add：AJAX（返回 JSON）
     *  兼容老接口：直接 POST 提交也会跳到购物车
     * ============================================================ */

    public function add()
    {
        $pid    = (int)I('id');
        $number = max(1, (int)I('number', 1));
        $attr   = trim((string)I('attr'));  // 规格 "色:黑;尺码:M"
        $color  = I('color');
        $size   = I('size');
        $from   = I('from');  // buy=立即购买

        $p = M('Product')->find($pid);
        if (!$p) {
            if ($this->_isAjax()) json_out(array('code' => 1, 'msg' => '商品不存在'));
            $this->error('商品不存在');
        }

        $Cart = M('Cart');
        $where = $this->_cartWhere();
        $where['product_id'] = $pid;
        $where['attr']       = $attr;
        $row = $Cart->where($where)->find();
        if ($row) {
            $Cart->where(array('id' => $row['id']))->setInc('number', $number);
        } else {
            $Cart->add(array(
                'userid'         => $this->uid,
                'sessionid'      => $this->uid ? '' : $this->cartSessionKey(),
                'product_id'     => $pid,
                'product_thumb'  => $p['thumb'],
                'product_name'   => $p['title'],
                'product_url'    => U('My/detail', array('id' => $pid)),
                'product_price'  => $p['pro_price'] ?: 0,
                'price'          => $p['pro_price'] ?: 0,
                'number'         => $number,
                'attr'           => $attr,
                'color'          => $color,
                'size'           => $size,
                'add_time'       => time(),
            ));
        }

        if ($this->_isAjax()) {
            $newCount = (int)$Cart->where($this->_cartWhere())->sum('number');
            json_out(array('code' => 0, 'msg' => '已加入购物车', 'count' => $newCount));
        }

        if ($from === 'buy') {
            redirect(U('Cart/checkout'));
        }
        redirect(U('Cart/index'));
    }

    /* ============================================================
     * 更新数量（AJAX）
     * ============================================================ */

    public function update()
    {
        $id  = (int)I('id');
        $num = max(1, (int)I('number', 1));
        $row = M('Cart')->where(array_merge($this->_cartWhere(), array('id' => $id)))->find();
        if (!$row) json_out(array('code' => 1, 'msg' => '记录不存在'));
        M('Cart')->where(array('id' => $id))->save(array('number' => $num));

        // 重新计算
        $items = $this->_loadCart();
        $s     = $this->_summary($items);
        json_out(array(
            'code'     => 0,
            'subtotal' => $s['subtotal_text'],
            'total'    => $s['total_text'],
            'count'    => $s['count'],
            'point'    => $s['point'],
        ));
    }

    /* ============================================================
     * 删除行
     * ============================================================ */

    public function remove()
    {
        $id = (int)I('id');
        M('Cart')->where(array_merge($this->_cartWhere(), array('id' => $id)))->delete();
        $items = $this->_loadCart();
        $s     = $this->_summary($items);
        if ($this->_isAjax()) {
            json_out(array(
                'code'     => 0,
                'subtotal' => $s['subtotal_text'],
                'total'    => $s['total_text'],
                'count'    => $s['count'],
                'empty'    => empty($items) ? 1 : 0,
            ));
        }
        redirect(U('Cart/index'));
    }

    /**
     * 清空
     */
    public function clear()
    {
        M('Cart')->where($this->_cartWhere())->delete();
        $this->success('已清空购物车');
    }

    /**
     * 应用优惠券（演示：直接减免 ¥5000）
     */
    public function coupon()
    {
        $code = strtoupper(trim(I('code')));
        $items = $this->_loadCart();
        $s = $this->_summary($items);
        $discount = 0;
        if ($code === 'KURA10') {       // 演示：95 折
            $discount = round($s['subtotal'] * 0.05);
        } elseif ($code === 'NEW5000') {
            $discount = min(5000, $s['subtotal']);
        } elseif ($code === '') {
            json_out(array('code' => 1, 'msg' => '请输入优惠券码'));
        } else {
            json_out(array('code' => 1, 'msg' => '该优惠券码无效'));
        }
        cookie('cart_coupon', json_encode(array('code' => $code, 'discount' => $discount)), 1800);
        json_out(array('code' => 0, 'msg' => '已使用 '.format_price($discount).' 优惠', 'discount' => $discount));
    }

    /* ============================================================
     * 结算页
     * ============================================================ */

    public function checkout()
    {
        require_login();
        $items = $this->_loadCart();
        if (empty($items)) redirect(U('Cart/index'));

        // 默认地址
        $defaultAddr = M('UserAddress')->where(array('userid' => $this->uid, 'isdefault' => 1))->find();
        if (!$defaultAddr) {
            $defaultAddr = M('UserAddress')->where(array('userid' => $this->uid))->order('id DESC')->find();
        }
        $addrList = M('UserAddress')->where(array('userid' => $this->uid))
            ->order('isdefault DESC, id DESC')->select();
        foreach ($addrList as &$a) {
            $Area = M('Area');
            $a['region'] = trim(
                $Area->where(array('id' => $a['province']))->getField('name').' '.
                $Area->where(array('id' => $a['city']))->getField('name').' '.
                $Area->where(array('id' => $a['area']))->getField('name')
            );
        }

        // 配送方式
        $shippings = M('Shipping')->where(array('status' => 1))->order('listorder ASC')->select();

        // 支付方式
        $payments = M('Payment')->where(array('status' => 1))->order('listorder ASC')->select();

        // 优惠券（演示：从 user_coupon 取未使用）
        $coupons = M('UserCoupon')->where(array('userid' => $this->uid, 'isuse' => 0, 'status' => 1))
            ->order('id DESC')->limit(5)->select();
        foreach ($coupons as &$c) {
            $c['coupon'] = M('Coupon')->find($c['cid']);
        }

        $summary = $this->_summary($items);

        // 恢复优惠券
        $coupon = $this->_readCoupon();
        if ($coupon) {
            $summary['discount']      = $coupon['discount'];
            $summary['discount_text'] = format_price($coupon['discount']);
            $summary['total']        -= $coupon['discount'];
            $summary['total_text']    = format_price($summary['total']);
            $summary['point']         = (int)floor($summary['total'] / 100);
        }

        $this->assign(array(
            'page_title'  => 'レジへ進む | '.$this->site['name'],
            'items'       => $items,
            'summary'     => $summary,
            'defaultAddr' => $defaultAddr,
            'addrList'    => $addrList,
            'shippings'   => $shippings,
            'payments'    => $payments,
            'coupons'     => $coupons,
            'coupon_used' => $coupon,
        ));
        $this->display('checkout');
    }

    /* ============================================================
     * 提交订单
     * ============================================================ */

    public function submit()
    {
        require_login();
        if (!IS_POST) $this->error('非法请求');

        $items = $this->_loadCart();
        if (empty($items)) $this->error('购物车为空');

        $addrId = (int)I('address_id');
        $payId  = (int)I('pay_id');
        $shipId = (int)I('shipping_id');
        $note   = I('note');

        $addr = M('UserAddress')->where(array('id' => $addrId, 'userid' => $this->uid))->find();
        if (!$addr) $this->error('请选择收货地址');

        $pay  = $payId  ? M('Payment')->find($payId)  : null;
        $ship = $shipId ? M('Shipping')->find($shipId) : null;

        $summary = $this->_summary($items);
        $coupon  = $this->_readCoupon();
        if ($coupon) {
            $summary['discount'] = $coupon['discount'];
            $summary['total']   -= $coupon['discount'];
        }
        $total = max(0, $summary['total']);

        $Area = M('Area');
        $region = trim(
            $Area->where(array('id' => $addr['province']))->getField('name').' '.
            $Area->where(array('id' => $addr['city']))->getField('name').' '.
            $Area->where(array('id' => $addr['area']))->getField('name').' '.
            $addr['address1'].$addr['address2'].$addr['address3']
        );

        $Order = M('Order');
        $sn = build_order_sn();
        $now = time();
        $orderData = array(
            'sn'             => $sn,
            'userid'         => $this->uid,
            'status'         => 0,
            'pay_status'     => 0,
            'shipping_status'=> 0,
            'consignee'      => $addr['consignee1'],
            'country'        => 0,
            'province'       => (int)$addr['province'],
            'city'           => (int)$addr['city'],
            'area'           => (int)$addr['area'],
            'address'        => $region,
            'zipcode'        => $addr['zip'],
            'tel'            => $addr['tel1'],
            'mobile'         => $addr['mobile'],
            'email'          => $addr['email'],
            'shipping_id'    => $shipId,
            'shipping_name'  => $ship['name'] ?: '标准快递',
            'shipping_fee'   => $summary['shipping_fee'],
            'pay_id'         => $payId,
            'pay_name'       => $pay['pay_name'] ?: '在线支付',
            'pay_code'       => $pay['pay_code'] ?: 'online',
            'amount'         => $summary['subtotal'],
            'order_amount'   => $total,
            'discount'       => $summary['discount'],
            'point'          => $summary['point'],
            'add_time'       => $now,
            'postmessage'    => $note,
            'note'           => $note,
        );
        $orderId = $Order->add($orderData);
        if (!$orderId) $this->error('订单提交失败');

        // 订单商品
        $OrderData = M('OrderData');
        foreach ($items as $it) {
            $OrderData->add(array(
                'userid'         => $this->uid,
                'order_id'       => $orderId,
                'product_id'     => $it['product_id'],
                'product_thumb'  => $it['product_thumb'],
                'product_name'   => $it['product_name'],
                'product_url'    => $it['product_url'],
                'product_price'  => $it['product_price'],
                'price'          => $it['price'],
                'number'         => $it['number'],
                'attr'           => $it['attr'],
                'color'          => $it['color'],
                'size'           => $it['size'],
                'add_time'       => $now,
            ));
            // 销量 +1
            M('Product')->where(array('id' => $it['product_id']))->setInc('buys', $it['number']);
        }

        // 清空购物车
        M('Cart')->where($this->_cartWhere())->delete();
        cookie('cart_coupon', null);

        redirect(U('Cart/done', array('sn' => $sn)));
    }

    /* ============================================================
     * 订单完成 / 支付（演示）
     * ============================================================ */

    public function done()
    {
        require_login();
        $sn  = I('sn');
        $row = M('Order')->where(array('sn' => $sn, 'userid' => $this->uid))->find();
        if (!$row) $this->display('Public:404'); exit;
        $items = M('OrderData')->where(array('order_id' => $row['id']))->select();
        $this->assign(array(
            'page_title' => '注文完了 | '.$this->site['name'],
            'order'      => $row,
            'items'      => $items,
        ));
        $this->display('done');
    }

    /**
     * 模拟支付（演示）：跳转 done 时把订单置为已付款
     */
    public function pay()
    {
        require_login();
        $sn  = I('sn');
        $row = M('Order')->where(array('sn' => $sn, 'userid' => $this->uid))->find();
        if (!$row) $this->error('订单不存在');
        if ((int)$row['pay_status'] === 1) redirect(U('Cart/done', array('sn' => $sn)));

        M('Order')->where(array('id' => $row['id']))->save(array(
            'status'     => 1,
            'pay_status' => 1,
            'pay_time'   => time(),
        ));
        // 加积分
        if ($row['point'] > 0) {
            M('User')->where(array('id' => $this->uid))->setInc('point', $row['point']);
        }
        $this->success('支付成功！', U('Cart/done', array('sn' => $sn)));
    }

    /* ============================================================
     * 内部
     * ============================================================ */

    /**
     * 读取购物车（按登录 / 未登录）
     */
    private function _loadCart()
    {
        $items = M('Cart')->where($this->_cartWhere())->order('id DESC')->select();
        foreach ($items as &$it) {
            $it['price_num']  = (float)$it['product_price'];
            $it['subtotal_n'] = $it['price_num'] * (int)$it['number'];
            $it['thumb_url']  = upload_url($it['product_thumb']);
            $it['price_text'] = format_price($it['price_num']);
            $it['subtotal_text'] = format_price($it['subtotal_n']);
        }
        return $items;
    }

    /**
     * 购物车 where
     */
    private function _cartWhere()
    {
        if ($this->uid) return array('userid' => $this->uid);
        return array('sessionid' => $this->cartSessionKey());
    }

    /**
     * 汇总
     */
    private function _summary($items)
    {
        $subtotal = 0;
        $count = 0;
        foreach ($items as $it) {
            $subtotal += $it['price_num'] * (int)$it['number'];
            $count    += (int)$it['number'];
        }
        $shippingFee = $subtotal >= 5000 ? 0 : 800;     // 演示：满 5000 包邮
        $giftFee     = 0;                                // 演示：默认无礼品包装
        $total       = $subtotal + $shippingFee + $giftFee;
        $point       = (int)floor($total / 100);

        return array(
            'subtotal'      => $subtotal,
            'subtotal_text' => format_price($subtotal),
            'shipping_fee'  => $shippingFee,
            'shipping_text' => $shippingFee ? format_price($shippingFee) : '無料',
            'gift_fee'      => $giftFee,
            'gift_text'     => format_price($giftFee),
            'discount'      => 0,
            'discount_text' => format_price(0),
            'total'         => $total,
            'total_text'    => format_price($total),
            'point'         => $point,
            'count'         => $count,
        );
    }

    private function _readCoupon()
    {
        $raw = cookie('cart_coupon');
        if (!$raw) return null;
        $d = json_decode($raw, true);
        return is_array($d) ? $d : null;
    }

    private function _isAjax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_GET['do']) && $_GET['do'] === 'add')
            || strtolower((string)I('format')) === 'json';
    }
}