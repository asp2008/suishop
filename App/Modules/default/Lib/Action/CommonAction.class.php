<?php
/**
 * 前台公用 Action 基类（Default 模块）
 * 负责：站点配置、全站栏目、导航、购物车数量、登录会员、模板 assign
 */

class CommonAction extends Action
{
    /** @var array 站点配置 */
    protected $site;

    /** @var array 顶部导航（一级分类 + 顶级文章分类） */
    protected $nav = array();

    /** @var int 购物车数量 */
    protected $cartCount = 0;

    /** @var int 当前会员 id（0 表示未登录） */
    protected $uid = 0;

    /** @var array|null 当前会员 */
    protected $member = null;

    public function _initialize()
    {
        header('Content-Type: text/html; charset=utf-8');

        // 站点配置
        $this->site = array(
            'name'        => CFG('site_name')        ?: '蔵市 KURA-ICHI',
            'slogan'      => CFG('site_slogan')      ?: '日本の逸品をお届けする通販サイト',
            'keywords'    => CFG('site_keywords')    ?: '日本,工艺品,陶器,蔵市',
            'description' => CFG('site_description') ?: '蔵市 - 全国的窑元、蔵元、工匠合作，严选日本好物。',
            'icp'         => CFG('site_icp')         ?: '京ICP备 0000000 号',
            'tel'         => CFG('site_tel')         ?: '400-000-0000',
            'email'       => CFG('site_email')       ?: 'support@suishop.com',
            'notice'      => CFG('site_notice')      ?: '期間限定：全品送料無料キャンペーン実施中｜会員登録で500ポイントプレゼント',
            'logo'        => CFG('site_logo')        ?: '__IMG__/logo.svg',
        );

        // 顶部导航：一级商品分类（catid 顶级、ismenu=1、按 listorder）
        $this->nav = M('Category')->where(array('parentid' => 0, 'ismenu' => 1, 'isdel' => 0, 'module' => 'Product'))
            ->order('listorder ASC, id ASC')->limit(10)->select();

        // 顶部品牌入口
        $brands = M('Brand')->where(array('status' => 1))->order('listorder ASC, id ASC')->limit(8)->select();

        // 当前会员
        if (session('?user')) {
            $this->member = session('user');
            $this->uid    = (int)$this->member['id'];
        }

        // 购物车数量：未登录按 sessionid，已登录按 userid
        $cartM = M('Cart');
        $where = array();
        if ($this->uid) {
            $where['userid'] = $this->uid;
        } else {
            $where['sessionid'] = $this->cartSessionKey();
        }
        $this->cartCount = (int)$cartM->where($where)->sum('number');
        if (!$this->cartCount) $this->cartCount = 0;

        // 头部 / 底部变量
        $this->assign('site',        $this->site);
        $this->assign('nav',         $this->nav);
        $this->assign('brands',      $brands);
        $this->assign('cartCount',   $this->cartCount);
        $this->assign('member',      $this->member);
        $this->assign('uid',         $this->uid);
        $this->assign('page_title',  $this->site['name']);
        $this->assign('page_key',    $this->site['keywords']);
        $this->assign('page_desc',   $this->site['description']);
        $this->assign('nav_curr',    strtolower(ACTION_NAME));
        $this->assign('controller',  strtolower(MODULE_NAME));
        $this->assign('is_login',    $this->uid > 0);
    }

    /**
     * 购物车 session key（未登录用）
     */
    protected function cartSessionKey()
    {
        $sid = cookie('cart_sid');
        if (!$sid) {
            $sid = md5(uniqid('cart_', true) . mt_rand());
            cookie('cart_sid', $sid, 86400 * 30);
        }
        return $sid;
    }

    /**
     * 空操作 → 404 模板
     */
    public function _empty()
    {
        $this->display('Public:404');
        exit;
    }
}