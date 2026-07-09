<?php
/**
 * Ajax 接口：省市区联动 / 验证码 / 收藏切换 / 喜欢切换 / 库存检查 / 留言
 *
 * 所有方法返回 JSON，统一格式 {code:0/1, msg:'', data:...}
 */

class AjaxAction extends Action
{
    public function _initialize()
    {
        header('Content-Type: application/json; charset=utf-8');
        // 演示环境关闭 CSRF 严格校验；线上请打开
        // parent::_initialize();
    }

    /**
     * 省市区级联（div_area）
     *  parent 默认 0，返回子级列表
     */
    public function area()
    {
        $parent = (int)I('parent', 0);
        $list   = M('Area')->where(array('parentid' => $parent))->order('listorder ASC, id ASC')->select();
        $this->_out(0, '', $list);
    }

    /**
     * 加入收藏 / 取消收藏
     */
    public function favorite()
    {
        $uid = (int)session('user.id');
        if (!$uid) $this->_out(401, '请先登录');
        $proid = (int)I('id');
        $op    = I('op', 'add');
        $M     = M('UserCollect');
        if ($op === 'del') {
            $M->where(array('userid' => $uid, 'proid' => $proid))->delete();
            $this->_out(0, '已取消收藏');
        }
        $has = $M->where(array('userid' => $uid, 'proid' => $proid))->find();
        if ($has) $this->_out(0, '已收藏');
        $M->add(array(
            'userid' => $uid,
            'proid'  => $proid,
            'time'   => time(),
            'ip'     => get_client_ip(),
        ));
        $this->_out(0, '已加入收藏');
    }

    /**
     * 验证码校验（简单后端校验）
     *  生成图片验证码：?do=verify
     */
    public function verify()
    {
        // 输出 PNG
        $code = $this->_randCode(4);
        session('verify_code', strtolower($code));

        // 用 gd 库生成简单图
        $w = 90; $h = 32;
        $im = imagecreatetruecolor($w, $h);
        $bg = imagecolorallocate($im, 245, 245, 247);
        $fg = imagecolorallocate($im, 29, 29, 31);
        imagefilledrectangle($im, 0, 0, $w, $h, $bg);
        // 干扰点
        for ($i = 0; $i < 200; $i++) {
            $c = imagecolorallocate($im, mt_rand(180, 230), mt_rand(180, 230), mt_rand(180, 230));
            imagesetpixel($im, mt_rand(0, $w), mt_rand(0, $h), $c);
        }
        // 字符（用内置字体，避免字体路径依赖）
        $len = strlen($code);
        for ($i = 0; $i < $len; $i++) {
            $x = 14 + $i * 18;
            $y = mt_rand(8, 12);
            imagestring($im, 5, $x, $y, $code[$i], $fg);
        }
        // 干扰线
        for ($i = 0; $i < 3; $i++) {
            $c = imagecolorallocate($im, mt_rand(150, 200), mt_rand(150, 200), mt_rand(150, 200));
            imageline($im, mt_rand(0, $w), mt_rand(0, $h), mt_rand(0, $w), mt_rand(0, $h), $c);
        }
        header('Content-Type: image/png');
        imagepng($im); imagedestroy($im);
        exit;
    }

    /**
     * 验证登录验证码
     */
    public function check_verify()
    {
        $code = strtolower(trim(I('code')));
        $sess = session('verify_code');
        if (!$sess) $this->_out(1, '验证码已过期');
        if ($code !== $sess) $this->_out(1, '验证码错误');
        $this->_out(0, '通过');
    }

    /**
     * 库存检查（详情页选规格后回查）
     *  入参 id, attr, number
     */
    public function stock()
    {
        $id     = (int)I('id');
        $number = (int)I('number', 1);
        $p = M('Product')->find($id);
        if (!$p) $this->_out(1, '商品不存在');
        $stock = (int)$p['stock'];
        $this->_out(0, '', array(
            'stock'     => $stock,
            'available' => $stock >= $number ? 1 : 0,
            'number'    => $number,
        ));
    }

    /**
     * 喜欢 / 点赞（商品）
     */
    public function digg()
    {
        $id = (int)I('id');
        M('Product')->where(array('id' => $id))->setInc('hits', 1);
        $this->_out(0, '已点赞');
    }

    /**
     * 全站搜索建议（自动补全）
     *  q=关键词
     */
    public function suggest()
    {
        $q = trim((string)I('q'));
        if ($q === '') $this->_out(0, '', array());
        $rows = M('Product')->field('id,title,thumb,pro_price')
            ->where(array('status' => 1, 'title' => array('LIKE', '%'.$q.'%')))
            ->order('id DESC')->limit(8)->select();
        foreach ($rows as &$r) {
            $r['thumb_url'] = upload_url($r['thumb']);
            $r['price']     = format_price($r['pro_price']);
            $r['url']       = U('My/detail', array('id' => $r['id']));
        }
        $this->_out(0, '', $rows);
    }

    /**
     * 留言 / 反馈
     */
    public function feedback()
    {
        $data = array(
            'title'      => I('title'),
            'content'    => I('content'),
            'uname'      => I('uname'),
            'email'      => I('email'),
            'tel'        => I('tel'),
            'createtime' => time(),
            'status'     => 0,
        );
        if (!$data['title'] || !$data['content']) $this->_out(1, '请填写完整');
        $data['userid']   = (int)session('user.id');
        $data['username'] = $data['userid'] ? session('user.username') : $data['uname'];
        M('Feedback')->add($data);
        $this->_out(0, '已收到，感谢您的反馈');
    }

    /**
     * 领取优惠券（演示用）
     */
    public function coupon_take()
    {
        $uid = (int)session('user.id');
        if (!$uid) $this->_out(401, '请先登录');
        $cid = (int)I('id');
        $c   = M('Coupon')->find($cid);
        if (!$c) $this->_out(1, '优惠券不存在');

        $has = M('UserCoupon')->where(array('userid' => $uid, 'cid' => $cid, 'isuse' => 0))->find();
        if ($has) $this->_out(1, '已领取过该券');

        M('UserCoupon')->add(array(
            'userid'  => $uid,
            'cid'     => $cid,
            'isuse'   => 0,
            'status'  => 1,
            'addtime' => date('Y-m-d H:i:s'),
        ));
        $this->_out(0, '领取成功');
    }

    /**
     * 上传图片（演示用：保存到 Public/theme/upload）
     */
    public function upload()
    {
        if (empty($_FILES['file'])) $this->_out(1, '请选择文件');
        $cfg = array(
            'rootPath' => './Public/theme/upload/',
            'exts'     => array('jpg', 'jpeg', 'png', 'gif', 'webp'),
            'maxSize'  => 5 * 1024 * 1024,
            'saveName' => array('uniqid', ''),
        );
        $Upload = new Upload($cfg);
        $info   = $Upload->uploadOne($_FILES['file']);
        if (!$info) $this->_out(1, $Upload->getError());
        $path = '/Public/theme/upload/'.$info['savepath'].$info['savename'];
        $this->_out(0, '上传成功', array('url' => $path, 'path' => $path));
    }

    /**
     * 订阅邮件
     */
    public function subscribe()
    {
        $email = trim(I('email'));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $this->_out(1, '请输入正确的邮箱');
        $M = M('EmailSendlist');
        $has = $M->where(array('email' => $email))->find();
        if ($has) $this->_out(0, '您已在订阅列表中');
        $M->add(array('email' => $email, 'status' => 1, 'createtime' => time()));
        $this->_out(0, '订阅成功，感谢您的关注');
    }

    /* ===== 内部 ===== */

    private function _out($code, $msg = '', $data = null)
    {
        $ret = array('code' => $code, 'msg' => $msg);
        if ($data !== null) $ret['data'] = $data;
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function _randCode($n)
    {
        $str = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $out = '';
        for ($i = 0; $i < $n; $i++) {
            $out .= $str[mt_rand(0, strlen($str) - 1)];
        }
        return $out;
    }
}