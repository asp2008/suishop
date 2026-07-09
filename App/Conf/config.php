<?php
// +----------------------------------------------------------------------
// | 项目配置 - suishop
// +----------------------------------------------------------------------

return array(
    /* -------------------- 数据库 -------------------- */
    'DB_TYPE'    => 'mysql',
    'DB_HOST'    => '127.0.0.1',
    'DB_NAME'    => 'suishop',
    'DB_USER'    => 'root',
    'DB_PWD'     => '',
    'DB_PORT'    => 3306,
    'DB_PREFIX'  => 'div_',
    'DB_CHARSET' => 'utf8mb4',
    'DB_DEBUG'   => true,

    /* -------------------- URL -------------------- */
    'URL_MODEL'        => 0,         // 0=普通 ?m=&a=  1=pathinfo  2=rewrite
    'URL_PATHINFO_DEPR'=> '/',
    'URL_HTML_SUFFIX'  => '.html',
    'URL_CASE_INSENSITIVE' => true,

    /* -------------------- 默认模块 -------------------- */
    'DEFAULT_MODULE' => 'default',
    'DEFAULT_ACTION' => 'index',

    /* -------------------- 模板 -------------------- */
    'TMPL_ACTION_ERROR' => 'Public:error',
    'TMPL_ACTION_SUCCESS' => 'Public:success',
    'TMPL_EXCEPTION_FILE' => 'Public:error',
    'TMPL_DETECT_THEME' => false,
    'DEFAULT_THEME' => 'default',
    'TMPL_TEMPLATE_SUFFIX' => '.html',
    'TMPL_FILE_DEPR' => '/',
    'TMPL_ENGINE_TYPE' => 'Think',
    'TMPL_PARSE_STRING' => array(
        '__PUBLIC__' => __ROOT__.'/Public',
        '__THEME__'  => __ROOT__.'/Public/theme',
        '__CSS__'    => __ROOT__.'/Public/theme/css',
        '__JS__'     => __ROOT__.'/Public/theme/js',
        '__IMG__'    => __ROOT__.'/Public/theme/images',
        '__UPLOAD__' => __ROOT__.'/Public/theme/upload',
    ),

    /* -------------------- 会话 -------------------- */
    'SESSION_AUTO_START' => true,
    'SESSION_NAME'       => 'SUISHOP_SID',

    /* -------------------- 默认时区 -------------------- */
    'DEFAULT_TIMEZONE' => 'PRC',

    /* -------------------- 安全 -------------------- */
    'COOKIE_PREFIX'    => 'SUISHOP_',
    'COOKIE_EXPIRE'    => 3600,
    'COOKIE_DOMAIN'    => '',
    'COOKIE_PATH'      => '/',

    /* -------------------- 调试 -------------------- */
    'SHOW_PAGE_TRACE'  => true,
    'TMPL_L_DELIM'     => '{',
    'TMPL_R_DELIM'     => '}',
    /* -------------------- 错误信息显示 -------------------- */
    'ERROR_PAGE'    => 'Public:error',
    'SUCCESS_PAGE'  => 'Public:success',

    /* -------------------- 上传配置 -------------------- */
    'UPLOAD_ROOT'   => './Public/theme/upload/',
    'UPLOAD_URL'    => '/Public/theme/upload/',
);