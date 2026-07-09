<?php
// +----------------------------------------------------------------------
// | 公共函数库（App/Common/common.php）
// +----------------------------------------------------------------------

/**
 * 价格格式化（¥ + 千分位 + .00）
 */
function format_price($price) {
    if ($price === '' || $price === null) return '¥0';
    return '¥' . number_format((float)$price, 0, '.', ',');
}

/**
 * 时间戳转友好时间显示（例：2026年6月18日）
 */
function format_date($ts, $fmt = 'Y年n月j日') {
    if (!$ts) return '';
    if (is_string($ts) && !is_numeric($ts)) {
        $ts = strtotime($ts);
    }
    return date($fmt, (int)$ts);
}

/**
 * 友好时间（3 分钟前）
 */
function format_time_ago($ts) {
    if (!$ts) return '';
    $diff = time() - (int)$ts;
    if ($diff < 60)         return '刚刚';
    if ($diff < 3600)       return floor($diff/60).'分钟前';
    if ($diff < 86400)      return floor($diff/3600).'小时前';
    if ($diff < 86400*30)   return floor($diff/86400).'天前';
    return date('Y-m-d', (int)$ts);
}

/**
 * 截断摘要
 */
function summary($str, $len = 60) {
    $str = strip_tags((string)$str);
    $str = preg_replace('/\s+/', ' ', $str);
    return mb_strlen($str, 'utf-8') > $len ? mb_substr($str, 0, $len, 'utf-8').'…' : $str;
}

/**
 * 生成订单号
 */
function build_order_sn() {
    return 'KI' . date('YmdHis') . mt_rand(1000, 9999);
}

/**
 * 字符串截取（中英文）
 */
function mbsubstr($str, $start, $len) {
    return mb_substr((string)$str, (int)$start, (int)$len, 'utf-8');
}

/**
 * 上传路径（用于前端展示 /uploads/xxx.jpg）
 */
function upload_url($path) {
    if (!$path) return '';
    if (preg_match('#^https?://#i', $path)) return $path;
    if ($path[0] === '/') return $path;
    return '/Public/theme/upload/' . ltrim($path, './');
}

/**
 * 缩略图：原 URL → 限定大小
 * 若上传时已生成 thumb 字段，直接返回 thumb，否则原图
 */
function thumb_url($url, $size = '') {
    return upload_url($url);
}

/**
 * 输出 Json（Ajax 用）
 */
function json_out($data, $code = 200) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 读取 div_config（缓存到内存）
 */
function CFG($name = null) {
    static $cache = null;
    if ($cache === null) {
        $cache = array();
        $rows = M('Config')->getField('varname,value');
        if ($rows) $cache = $rows;
    }
    if ($name === null) return $cache;
    return isset($cache[$name]) ? $cache[$name] : '';
}

/**
 * 获取当前会员（未登录返回 null）
 */
function current_user() {
    return session('?user') ? session('user') : null;
}

/**
 * 必须登录（用于账户中心 / 下单）
 */
function require_login() {
    if (!current_user()) {
        redirect(U('Account/login', array('ref' => $_SERVER['REQUEST_URI'])));
        exit;
    }
}

/**
 * URL 拼接工具
 */
function suishop_url($action, $params = array()) {
    return U($action, $params);
}