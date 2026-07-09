<?php
// +----------------------------------------------------------------------
// | 调试模式配置（调试生效）
// +----------------------------------------------------------------------

return array(
    'LOG_LEVEL'        => 'EMERG,ALERT,CRIT,ERR,WARN,NOTIC,INFO,DEBUG,SQL',
    'LOG_RECORD'       => true,
    'LOG_EXCEPTION_RECORD' => true,
    'ERROR_MESSAGE'    => '页面错误！{:errorMsg}',
    'SHOW_ERROR_MSG'   => true,
    'TRACE_PAGE_TITLE' => 'suishop 页面 Trace',
    'TRACE_MAX_RECORD' => 50,
    'DB_FIELDS_CACHE'  => false,
    'TMPL_CACHE_ON'    => false,
);