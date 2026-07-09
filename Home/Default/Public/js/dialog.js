/**
 * KuraDialog — 可复用对话框 & Toast 工具库
 * ─────────────────────────────────────────────────────────
 * 依赖：jQuery 3+  /  dialog.css
 *
 * 使用方法：
 *
 *   // ① Dialog（模态框）
 *   KuraDialog.show({
 *     type    : 'error',          // 'error' | 'success' | 'warning' | 'info' | 'confirm'
 *     title   : 'タイトル',
 *     message : 'メッセージ内容',  // HTML 可用
 *     actions : [
 *       { label:'OK', style:'primary', onClick: function(close){ close(); } },
 *       { label:'キャンセル', style:'ghost' }
 *     ],
 *     closable: true              // 右上×ボタン（default: true）
 *   });
 *
 *   // ② Toast（右下通知）
 *   KuraDialog.toast({
 *     type   : 'success',         // 'success' | 'error' | 'warning' | 'info'
 *     title  : '保存しました',
 *     message: '変更内容が反映されました',
 *     duration: 4000              // ms（default: 4000, 0=永久）
 *   });
 *
 *   // ③ ローディング状態をボタンに付与 / 解除
 *   var restore = KuraDialog.loadingBtn($('#submitBtn'), '送信中…');
 *   // 通信完了後:
 *   restore();
 *
 * ─────────────────────────────────────────────────────────
 */

;(function (global, $) {
  'use strict';

  /* ── SVG アイコンセット ── */
  var ICONS = {
    error:   '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
    success: '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="8 12 11 15 16 9"/></svg>',
    warning: '<svg viewBox="0 0 24 24"><path d="M10.3 3.5L1.5 18A2 2 0 0 0 3.2 21h17.6a2 2 0 0 0 1.7-3L13.7 3.5a2 2 0 0 0-3.4 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    info:    '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    confirm: '<svg viewBox="0 0 24 24"><path d="M10.3 3.5L1.5 18A2 2 0 0 0 3.2 21h17.6a2 2 0 0 0 1.7-3L13.7 3.5a2 2 0 0 0-3.4 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    close:   '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    dismiss: '<svg viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
  };

  /* ── Overlay が 1 つだけ存在することを保証 ── */
  var $overlay = null;

  function getOverlay() {
    if (!$overlay || !$overlay.length) {
      $overlay = $('<div class="kc-overlay" id="kcOverlay" role="dialog" aria-modal="true"></div>');
      $('body').append($overlay);

      /* オーバーレイ自体をクリックして閉じる */
      $overlay.on('click', function (e) {
        if ($(e.target).is($overlay)) closeDialog();
      });

      /* Esc で閉じる */
      $(document).on('keydown.kcdialog', function (e) {
        if (e.key === 'Escape') closeDialog();
      });
    }
    return $overlay;
  }

  function closeDialog() {
    if (!$overlay) return;
    $overlay.removeClass('kc-open');
    $('body').css('overflow', '');
    setTimeout(function () { $overlay.empty(); }, 260);
    $(document).off('keydown.kcdialog');
  }

  /* ── ボタン HTML を生成 ── */
  function buildBtn(action, closeFn) {
    var style = action.style || 'primary';
    var $btn;

    if (style === 'ghost' || style === 'ghost-red') {
      $btn = $('<button class="kc-btn-ghost"></button>').text(action.label || 'OK');
      if (style === 'ghost-red') $btn.addClass('kc-red');
    } else {
      var btnClass = 'btn btn-block';
      if (style === 'primary')  btnClass += ' btn-dark';
      if (style === 'danger')   btnClass += ' btn-vermilion';
      if (style === 'outline')  btnClass += ' btn-outline';
      $btn = $('<button></button>').addClass(btnClass).text(action.label || 'OK');
    }

    $btn.on('click', function () {
      if (typeof action.onClick === 'function') {
        action.onClick(closeFn, $btn);
      } else {
        closeFn();
      }
    });
    return $btn;
  }

  /* ────────────────────────────────────────
     KuraDialog.show(options)
  ──────────────────────────────────────── */
  function show(opts) {
    opts = $.extend({
      type    : 'info',
      title   : '',
      message : '',
      actions : [{ label: 'OK', style: 'primary' }],
      closable: true,
      size    : ''      // '' | 'sm' | 'lg'
    }, opts);

    var $ov = getOverlay();

    var dialogClass = 'kc-dialog' + (opts.size ? ' kc-' + opts.size : '');
    var $box = $('<div></div>').addClass(dialogClass);

    /* 閉じるボタン */
    if (opts.closable) {
      var $closeBtn = $('<button class="kc-close-btn" aria-label="閉じる"></button>').html(ICONS.close);
      $closeBtn.on('click', close);
      $box.append($closeBtn);
    }

    /* アイコン */
    $box.append(
      $('<div></div>')
        .addClass('kc-dialog-icon kc-icon-' + opts.type)
        .html(ICONS[opts.type] || ICONS.info)
    );

    /* タイトル */
    if (opts.title) {
      $box.append($('<div class="kc-dialog-title"></div>').html(opts.title));
    }

    /* 本文 */
    if (opts.message) {
      $box.append($('<div class="kc-dialog-body"></div>').html(opts.message));
    }

    /* カスタムコンテンツ */
    if (opts.content) {
      $box.append(opts.content);
    }

    /* ボタン */
    var $footer = $('<div class="kc-dialog-footer"></div>');
    if (opts.actions && opts.actions.length >= 2) $footer.addClass('kc-row');
    $.each(opts.actions || [], function (_, action) {
      $footer.append(buildBtn(action, close));
    });
    $box.append($footer);

    $ov.empty().append($box);
    $('body').css('overflow', 'hidden');

    /* アニメーション開始（次フレーム）*/
    requestAnimationFrame(function () { $ov.addClass('kc-open'); });

    function close() { closeDialog(); }

    /* 外部からダイアログを閉じるための参照を返す */
    return { close: close };
  }

  /* ────────────────────────────────────────
     KuraDialog.toast(options)
  ──────────────────────────────────────── */
  var $toastContainer = null;

  function ensureToastContainer() {
    if (!$toastContainer || !$toastContainer.length) {
      $toastContainer = $('<div id="kc-toast-container"></div>');
      $('body').append($toastContainer);
    }
    return $toastContainer;
  }

  function toast(opts) {
    opts = $.extend({
      type    : 'info',
      title   : '',
      message : '',
      duration: 4000
    }, opts);

    var $c = ensureToastContainer();

    var $t = $('<div class="kc-toast kc-t-' + opts.type + '"></div>');

    /* アイコン */
    $t.append(
      $('<div class="kc-toast-icon"></div>').html(ICONS[opts.type] || ICONS.info)
    );

    /* テキスト */
    var $content = $('<div class="kc-toast-content"></div>');
    if (opts.title)   $content.append($('<div class="kc-toast-title"></div>').text(opts.title));
    if (opts.message) $content.append($('<div class="kc-toast-msg"></div>').html(opts.message));
    $t.append($content);

    /* 閉じるボタン */
    var $dismiss = $('<button class="kc-toast-dismiss" aria-label="閉じる"></button>').html(ICONS.dismiss);
    $dismiss.on('click', function () { removeToast($t); });
    $t.append($dismiss);

    $c.append($t);

    /* 自動消去 */
    var timer;
    if (opts.duration > 0) {
      timer = setTimeout(function () { removeToast($t); }, opts.duration);
    }

    $t.on('mouseenter', function () { clearTimeout(timer); });
    $t.on('mouseleave', function () {
      if (opts.duration > 0) timer = setTimeout(function () { removeToast($t); }, opts.duration);
    });

    return {
      dismiss: function () { clearTimeout(timer); removeToast($t); }
    };
  }

  function removeToast($t) {
    $t.addClass('kc-toast-out');
    setTimeout(function () { $t.remove(); }, 280);
  }

  /* ────────────────────────────────────────
     KuraDialog.loadingBtn($btn, label)
     ボタンをローディング状態にして元に戻す関数を返す
  ──────────────────────────────────────── */
  function loadingBtn($btn, label) {
    var origHtml     = $btn.html();
    var origDisabled = $btn.prop('disabled');
    var spinnerDark  = $btn.hasClass('btn-outline');

    var spinnerClass = 'kc-spinner' + (spinnerDark ? ' kc-spinner-dark' : '');
    $btn.html('<span class="' + spinnerClass + '"></span>' + (label || '処理中…'));
    $btn.prop('disabled', true);

    return function restore(newLabel) {
      $btn.html(newLabel !== undefined ? newLabel : origHtml);
      $btn.prop('disabled', origDisabled);
    };
  }

  /* ────────────────────────────────────────
     公開 API
  ──────────────────────────────────────── */
  global.KuraDialog = {
    show       : show,
    toast      : toast,
    loadingBtn : loadingBtn,
    close      : closeDialog
  };

}(window, jQuery));
