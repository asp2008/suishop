<?php
/**
 * 前台综合 Action：首页、商品列表、商品详情、品牌、文章、搜索、收藏、留言
 *
 * 命名遵循 TP3.x 习惯，下划线开头方法为魔术方法（_initialize/_empty）。
 */
class MyAction extends CommonAction
{
    /* ============================================================
     * 首页 / 品牌专题 / 帮助
     * ============================================================ */

    /**
     * 首页
     */
    public function index()
    {
        // Hero 轮播：从 slide 取（fid=1 为首页大图）
        $hero = M('SlideData')->where(array('fid' => 1, 'status' => 1))
            ->order('listorder ASC, id ASC')->limit(5)->select();
        if (!$hero) $hero = array();

        // 分类入口（取最多 6 个一级）
        $cats = M('Category')->where(array('parentid' => 0, 'ismenu' => 1, 'isdel' => 0, 'module' => 'Product'))
            ->order('listorder ASC, id ASC')->limit(6)->select();

        // 推荐商品（取最新上架作为「編集部おすすめ」）
        $recommend = M('Product')->where(array('status' => 1))
            ->order('listorder ASC, id DESC')->limit(8)->select();
        $this->_fillProduct(&$recommend);

        // 季节限定
        $season = M('Product')->where(array('status' => 1))
            ->order('hits DESC, id DESC')->limit(4)->select();
        $this->_fillProduct(&$season);

        // 热销榜
        $hot = M('Product')->where(array('status' => 1))
            ->order('buys DESC, id DESC')->limit(6)->select();
        $this->_fillProduct(&$hot);

        // 品牌（顶部 tile）
        $brandsTile = M('Brand')->where(array('status' => 1))
            ->order('listorder ASC')->limit(4)->select();

        $this->assign(array(
            'page_title'  => $this->site['name'].' - '.$this->site['slogan'],
            'hero'        => $hero,
            'cats'        => $cats,
            'recommend'   => $recommend,
            'season'      => $season,
            'hot'         => $hot,
            'brandsTile'  => $brandsTile,
        ));
        $this->display('index');
    }

    /**
     * 商品列表 / 分类页
     * 支持：catid, brand, q, sort, p, min, max
     */
    public function lists()
    {
        $catid  = (int)$_GET['catid'];
        $brand  = (int)$_GET['brand'];
        $q      = trim((string)I('q'));
        $sort   = (string)I('sort', 'rec');
        $min    = (float)I('min', 0);
        $max    = (float)I('max', 0);
        $p      = max(1, (int)I('p', 1));

        $Product = M('Product');
        $where   = array('status' => 1);

        if ($catid) {
            // 含子分类
            $arrchildid = $this->_childIds('Category', $catid);
            $where['catid'] = array('IN', $arrchildid ?: array($catid));
        }
        if ($brand) {
            $where['pinpai'] = $brand;
        }
        if ($q !== '') {
            $where['title'] = array('LIKE', '%'.$q.'%');
        }
        if ($min > 0) {
            $where['pro_price'] = array('EGT', $min);
        }
        if ($max > 0) {
            $where['pro_price'] = array('ELT', $max);
        }
        if ($min > 0 && $max > 0) {
            $where['pro_price'] = array('BETWEEN', array($min, $max));
        }

        // 排序
        $order = 'listorder ASC, id DESC';
        switch ($sort) {
            case 'new':     $order = 'id DESC'; break;
            case 'price_a': $order = "CAST(pro_price AS DECIMAL(10,2)) ASC"; break;
            case 'price_d': $order = "CAST(pro_price AS DECIMAL(10,2)) DESC"; break;
            case 'sale':    $order = 'buys DESC, id DESC'; break;
            case 'rec':     $order = 'listorder ASC, id DESC'; break;
        }

        $pageSize = 12;
        $list = $this->_paged($Product, $where, $order, $pageSize, $p);
        $this->_fillProduct(&$list['data']);

        // 当前分类信息
        $curCat = $catid ? M('Category')->find($catid) : null;
        if ($curCat && $curCat['parentid']) {
            $curCat['parent'] = M('Category')->find($curCat['parentid']);
        }

        // 侧边分类
        $catsAll = M('Category')->where(array('parentid' => 0, 'ismenu' => 1, 'isdel' => 0, 'module' => 'Product'))
            ->order('listorder ASC')->select();
        $subCats = array();
        if ($curCat) {
            $subCats = M('Category')->where(array('parentid' => $curCat['id'], 'isdel' => 0, 'ismenu' => 1))
                ->order('listorder ASC')->select();
        }

        // 当前品牌
        $curBrand = $brand ? M('Brand')->find($brand) : null;

        // 推荐品牌（侧边栏）
        $brandsAll = M('Brand')->where(array('status' => 1))->order('listorder ASC')->limit(20)->select();

        $title = $curCat ? $curCat['catname'].' 一览' : '全部商品';
        $this->assign(array(
            'page_title' => $title.' | '.$this->site['name'],
            'list'       => $list['data'],
            'pager'      => $list['page'],
            'count'      => $list['count'],
            'curCat'     => $curCat,
            'subCats'    => $subCats,
            'catsAll'    => $catsAll,
            'curBrand'   => $curBrand,
            'brandsAll'  => $brandsAll,
            'q'          => $q,
            'sort'       => $sort,
            'min'        => $min,
            'max'        => $max,
            'catid'      => $catid,
            'brand'      => $brand,
        ));
        $this->display('lists');
    }

    /**
     * 商品详情
     */
    public function detail()
    {
        $id = (int)I('id');
        if (!$id) $this->error('商品不存在');
        $row = M('Product')->where(array('id' => $id, 'status' => 1))->find();
        if (!$row) $this->display('Public:404'); exit;

        // 更新浏览量（防刷新刷，每 session +1）
        $hitKey = 'hit_'.$id;
        if (!cookie($hitKey)) {
            M('Product')->where(array('id' => $id))->setInc('hits', 1);
            cookie($hitKey, 1, 600);
        }

        // 处理图片集
        $pics = array();
        if (!empty($row['pics'])) {
            $pics = array_filter(preg_split('/[\r\n,]+/', $row['pics']));
        }
        if (empty($pics) && !empty($row['thumb'])) {
            $pics = array($row['thumb']);
        }
        if (empty($pics)) {
            $pics = array('__IMG__/placeholder.svg');
        }
        $row['pics_arr'] = $pics;

        // 主分类（含父级）
        $cat = M('Category')->find($row['catid']);
        if ($cat && $cat['parentid']) {
            $cat['parent'] = M('Category')->find($cat['parentid']);
        }

        // 品牌
        $brand = $row['pinpai'] ? M('Brand')->find($row['pinpai']) : null;

        // 关联商品（同分类下其他商品，最多 4 个）
        $related = M('Product')->where(array('catid' => $row['catid'], 'status' => 1, 'id' => array('NEQ', $id)))
            ->order('id DESC')->limit(4)->select();
        $this->_fillProduct(&$related);

        // 评论（简单读取 div_feedback）
        $reviews = M('Feedback')->where(array('product_id' => $id, 'status' => 1))
            ->order('id DESC')->limit(6)->select();

        // 是否已收藏
        $isFav = 0;
        if ($this->uid) {
            $f = M('UserCollect')->where(array('userid' => $this->uid, 'proid' => $id))->find();
            $isFav = $f ? 1 : 0;
        }

        $this->assign(array(
            'page_title' => $row['title'].' | '.$this->site['name'],
            'product'    => $row,
            'cat'        => $cat,
            'brand'      => $brand,
            'related'    => $related,
            'reviews'    => $reviews,
            'isFav'      => $isFav,
        ));
        $this->display('detail');
    }

    /* ============================================================
     * 品牌
     * ============================================================ */

    /**
     * 品牌列表
     */
    public function brand()
    {
        $brands = M('Brand')->where(array('status' => 1))->order('listorder ASC, id ASC')->select();
        // 各品牌下商品数
        $Product = M('Product');
        foreach ($brands as &$b) {
            $b['goods_count'] = (int)$Product->where(array('pinpai' => $b['id'], 'status' => 1))->count();
        }
        $this->assign(array(
            'page_title' => '全部品牌 | '.$this->site['name'],
            'brands'     => $brands,
        ));
        $this->display('brand_lists');
    }

    /**
     * 品牌详情：展示品牌介绍 + 旗下商品
     */
    public function brand_show()
    {
        $id = (int)I('id');
        $b  = M('Brand')->where(array('id' => $id, 'status' => 1))->find();
        if (!$b) $this->display('Public:404'); exit;

        $list = M('Product')->where(array('pinpai' => $id, 'status' => 1))
            ->order('listorder ASC, id DESC')->limit(24)->select();
        $this->_fillProduct(&$list);

        $this->assign(array(
            'page_title' => $b['title'].' 品牌 | '.$this->site['name'],
            'brand'      => $b,
            'list'       => $list,
        ));
        $this->display('brand_show');
    }

    /* ============================================================
     * 文章 / 帮助 / 单页
     * ============================================================ */

    /**
     * 文章列表（栏目）
     * ?catid= 栏目 id
     */
    public function article_lists()
    {
        $catid = (int)I('catid');
        $where = array('status' => 1);
        if ($catid) {
            $where['catid'] = $catid;
        }
        $list = M('Article')->where($where)->order('listorder ASC, id DESC')->limit(20)->select();
        $cat  = $catid ? M('Category')->find($catid) : null;
        $this->assign(array(
            'page_title' => ($cat ? $cat['catname'] : '资讯中心').' | '.$this->site['name'],
            'list'       => $list,
            'cat'        => $cat,
            'catid'      => $catid,
        ));
        $this->display('article_lists');
    }

    /**
     * 文章详情
     */
    public function article_show()
    {
        $id = (int)I('id');
        $row = M('Article')->where(array('id' => $id, 'status' => 1))->find();
        if (!$row) $this->display('Public:404'); exit;

        $cat = M('Category')->find($row['catid']);
        $prev = M('Article')->where("catid={$row['catid']} AND id<{$id} AND status=1")->order('id DESC')->find();
        $next = M('Article')->where("catid={$row['catid']} AND id>{$id} AND status=1")->order('id ASC')->find();

        $this->assign(array(
            'page_title' => $row['title'].' | '.$this->site['name'],
            'article'    => $row,
            'cat'        => $cat,
            'prev'       => $prev,
            'next'       => $next,
        ));
        $this->display('article_show');
    }

    /**
     * 单页（关于我们 / 配送说明 / 隐私政策 …）
     * ?id=div_page.id
     */
    public function page()
    {
        $id  = (int)I('id');
        $row = M('Page')->where(array('id' => $id, 'status' => 1))->find();
        if (!$row) $this->display('Public:404'); exit;

        $this->assign(array(
            'page_title' => $row['title'].' | '.$this->site['name'],
            'page'       => $row,
        ));
        $this->display('page');
    }

    /**
     * 帮助/FAQ
     */
    public function faq()
    {
        $list = M('Faq')->where(array('status' => 1))->order('listorder ASC, id DESC')->limit(30)->select();
        $this->assign(array(
            'page_title' => 'よくあるご質問 | '.$this->site['name'],
            'list'       => $list,
        ));
        $this->display('faq');
    }

    /**
     * 友情链接
     */
    public function links()
    {
        $list = M('Link')->where(array('status' => 1))->order('listorder ASC')->select();
        $this->assign(array(
            'page_title' => '友情链接 | '.$this->site['name'],
            'list'       => $list,
        ));
        $this->display('links');
    }

    /* ============================================================
     * 收藏
     * ============================================================ */

    /**
     * 加入收藏（需登录）
     */
    public function favorite_add()
    {
        if (!$this->uid) json_out(array('code' => 401, 'msg' => '请先登录'));
        $id = (int)I('id');
        if (!$id) json_out(array('code' => 400, 'msg' => '参数错误'));
        $has = M('UserCollect')->where(array('userid' => $this->uid, 'proid' => $id))->find();
        if ($has) json_out(array('code' => 0, 'msg' => '已收藏过该商品'));
        M('UserCollect')->add(array(
            'userid'  => $this->uid,
            'proid'   => $id,
            'ip'      => get_client_ip(),
            'time'    => time(),
            'protype' => (int)I('protype', 0),
            'pinpai'  => (int)I('pinpai', 0),
        ));
        json_out(array('code' => 0, 'msg' => '已加入收藏'));
    }

    /**
     * 取消收藏
     */
    public function favorite_del()
    {
        if (!$this->uid) json_out(array('code' => 401, 'msg' => '请先登录'));
        $id = (int)I('id');
        M('UserCollect')->where(array('userid' => $this->uid, 'proid' => $id))->delete();
        json_out(array('code' => 0, 'msg' => '已取消收藏'));
    }

    /* ============================================================
     * 留言 / 反馈
     * ============================================================ */

    public function feedback()
    {
        if (IS_POST) {
            $data = array(
                'title'      => I('title'),
                'content'    => I('content'),
                'uname'      => I('uname'),
                'email'      => I('email'),
                'tel'        => I('tel'),
                'createtime' => time(),
                'status'     => 0,
            );
            if (empty($data['title']) || empty($data['content'])) {
                $this->error('请填写完整');
            }
            $data['userid'] = $this->uid;
            $data['username'] = $this->uid ? $this->member['username'] : $data['uname'];
            M('Feedback')->add($data);
            $this->success('提交成功，感谢您的反馈');
        }
        $this->display('feedback');
    }

    /**
     * 全站搜索
     */
    public function search()
    {
        $q = trim((string)I('q'));
        if ($q === '') {
            redirect(U('My/lists'));
        }
        $where = array('status' => 1);
        $where['title|keywords|description'] = array('LIKE', '%'.$q.'%');
        $list = M('Product')->where($where)->order('id DESC')->limit(40)->select();
        $this->_fillProduct(&$list);

        $this->assign(array(
            'page_title' => '搜索:'.$q.' | '.$this->site['name'],
            'q'          => $q,
            'list'       => $list,
            'count'      => count($list),
        ));
        $this->display('search');
    }

    /* ============================================================
     * 内部工具：补全商品信息（价格字符串 → 数字 + thumb）
     * ============================================================ */

    /**
     * 把记录集里的 pro_price / price 字符串转 float + 补 thumb
     * @param array $rows 二维引用数组
     */
    private function _fillProduct(&$rows)
    {
        if (!$rows) return;
        foreach ($rows as &$r) {
            $r['price_num'] = $this->_parsePrice($r['pro_price'] ?? $r['product_price'] ?? 0);
            $r['thumb_url'] = upload_url($r['thumb'] ?? '');
            $r['url']       = U('My/detail', array('id' => $r['id']));
            // 评价数（软查询，避免压力）
            $r['review_n']  = (int)M('Feedback')->where(array('product_id' => $r['id'], 'status' => 1))->count();
            // 评分（平均）
            $avg = M('Feedback')->where(array('product_id' => $r['id'], 'status' => 1))->avg('grade');
            $r['rating'] = $avg ? round((float)$avg, 1) : 5.0;
            // badge
            $badges = array();
            if ($r['buys'] > 100) $badges[] = array('text' => '人気', 'cls' => 'badge-gold');
            if (!empty($r['rec']) && strpos($r['rec'], 'new') !== false) $badges[] = array('text' => '新商品', 'cls' => '');
            if (mt_rand(0, 3) === 1) $badges[] = array('text' => '送料無料', 'cls' => '');
            $r['badges'] = $badges;
        }
    }

    /**
     * 把 pro_price "8800" / "8800.00" / "¥8,800" 等解析成 float
     */
    private function _parsePrice($val)
    {
        if (is_numeric($val)) return (float)$val;
        $val = preg_replace('/[^\d\.]/', '', (string)$val);
        return (float)$val;
    }

    /**
     * 取子分类 id 集合（含自身）
     */
    private function _childIds($tbl, $id)
    {
        static $cache = array();
        $key = $tbl.'_'.$id;
        if (isset($cache[$key])) return $cache[$key];
        $rows = M($tbl)->field('id')->where(array('parentid' => $id))->select();
        $ids = array($id);
        if ($rows) foreach ($rows as $r) $ids[] = (int)$r['id'];
        $cache[$key] = $ids;
        return $ids;
    }

    /**
     * 分页封装（原生分页）
     * @return array data, page(count), count
     */
    private function _paged($Model, $where, $order, $pageSize, $page)
    {
        $count = (int)$Model->where($where)->count();
        $Page  = new Page($count, $pageSize);
        $limit = $Page->firstRow.','.$Page->listRows;
        $data  = $Model->where($where)->order($order)->limit($limit)->select();
        $Page->setConfig('header', '件');
        $Page->setConfig('prev', '<');
        $Page->setConfig('next', '>');
        $Page->setConfig('theme', '%upPage% %linkPage% %downPage%');
        $show = $Page->show();
        return array('data' => $data, 'page' => $show, 'count' => $count);
    }
}