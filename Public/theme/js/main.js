/* ============================================================
 * suishop theme - main.js
 * jQuery required (TP3 not bundled jQuery; load via CDN)
 * ============================================================ */
$(function () {

  /* ---------- mobile nav toggle ---------- */
  $(".menu-toggle").on("click", function () {
    $(".main-nav").toggleClass("open");
    $(this).attr("aria-expanded", $(".main-nav").hasClass("open"));
  });

  /* ---------- favorite (heart) toggle (no login -> redirect) ---------- */
  $(document).on("click", ".fav-btn", function (e) {
    e.preventDefault();
    var $btn = $(this);
    var url = $btn.data("url");
    if (url) {
      window.location.href = url;
      return;
    }
    $btn.toggleClass("active");
  });

  /* ---------- favorite (heart) via Ajax ---------- */
  $(document).on("click", ".fav-ajax", function (e) {
    e.preventDefault();
    var $btn = $(this);
    var id   = $btn.data("id");
    var op   = $btn.hasClass("active") ? "del" : "add";
    $btn.prop("disabled", true);
    $.post(SUISHOP.root + "?m=Ajax&a=favorite", { id: id, op: op }, function (res) {
      $btn.prop("disabled", false);
      if (res.code === 0) {
        if (op === "add") $btn.addClass("active");
        else $btn.removeClass("active");
        toast(res.msg || "操作成功");
      } else if (res.code === 401) {
        window.location.href = SUISHOP.root + "?m=Account&a=login";
      } else {
        toast(res.msg || "操作失败");
      }
    }, "json").fail(function () {
      $btn.prop("disabled", false);
      toast("网络异常，请稍后再试");
    });
  });

  /* ---------- quantity stepper (通用) ---------- */
  $(document).on("click", ".qty-stepper .minus", function () {
    var $input = $(this).siblings("input");
    var min = parseInt($input.data("min") || 1, 10);
    var val = Math.max(min, (parseInt($input.val(), 10) || min) - 1);
    $input.val(val).trigger("change");
  });
  $(document).on("click", ".qty-stepper .plus", function () {
    var $input = $(this).siblings("input");
    var val = (parseInt($input.val(), 10) || 1) + 1;
    $input.val(val).trigger("change");
  });

  /* ---------- gallery thumbnail switch (detail page) ---------- */
  $(document).on("click", ".gallery-thumbs button", function () {
    var src = $(this).find("img").attr("src");
    $(".gallery-main img").attr("src", src);
    $(".gallery-thumbs button").removeClass("active");
    $(this).addClass("active");
  });

  /* ---------- detail tabs ---------- */
  $(document).on("click", ".detail-tabs button", function () {
    var target = $(this).data("tab");
    $(".detail-tabs button").removeClass("active");
    $(this).addClass("active");
    $(".tab-panel").removeClass("active");
    $("#" + target).addClass("active");
  });

  /* ---------- mypage accordion (FAQ etc) ---------- */
  $(document).on("click", ".accordion-head", function () {
    var $body = $(this).next(".accordion-body");
    var isOpen = $(this).hasClass("open");
    $(".accordion-head").removeClass("open");
    $(".accordion-body").removeClass("open").slideUp(0);
    if (!isOpen) {
      $(this).addClass("open");
      $body.addClass("open").slideDown(180);
    }
  });

  /* ---------- active state by data-page ---------- */
  var page = $("body").data("page");
  if (page) {
    $(".mypage-nav a[data-page='" + page + "']").addClass("active");
    $(".main-nav a[data-page='" + page + "']").addClass("active");
  }

  /* ---------- simple front-end validation: login ---------- */
  $("#loginForm").on("submit", function (e) {
    e.preventDefault();
    var ok = true;
    var $u = $("#loginUsername");
    var $p = $("#loginPassword");
    if (!$u.val()) { $u.closest(".field").addClass("has-error"); ok = false; }
    else { $u.closest(".field").removeClass("has-error"); }
    if (!$p.val() || $p.val().length < 6) { $p.closest(".field").addClass("has-error"); ok = false; }
    else { $p.closest(".field").removeClass("has-error"); }
    if (ok) this.submit();
  });

  /* ---------- register ---------- */
  $("#registerForm").on("submit", function (e) {
    e.preventDefault();
    var ok = true;
    $(this).find("input[required]").each(function () {
      var $f = $(this).closest(".field");
      if (!$(this).val()) { $f.addClass("has-error"); ok = false; }
      else { $f.removeClass("has-error"); }
    });
    var p1 = $("#regPassword").val();
    var p2 = $("#regPasswordConfirm").val();
    if (p1 && p2 && p1 !== p2) {
      $("#regPasswordConfirm").closest(".field").addClass("has-error");
      ok = false;
    }
    if (p1 && p1.length < 6) {
      $("#regPassword").closest(".field").addClass("has-error");
      ok = false;
    }
    if (ok) this.submit();
  });

  /* ---------- list page sort ---------- */
  $("#sortSelect").on("change", function () {
    var url = new URL(window.location.href);
    url.searchParams.set("sort", $(this).val());
    window.location.href = url.toString();
  });

  /* ---------- list filter chip removal ---------- */
  $(document).on("click", ".tag-chip button", function () {
    var url = new URL(window.location.href);
    url.searchParams.delete("min");
    url.searchParams.delete("max");
    url.searchParams.delete("brand");
    window.location.href = url.toString();
  });

  /* ---------- header search ---------- */
  $(".search-form").on("submit", function (e) {
    e.preventDefault();
    var q = $(this).find("input").val().trim();
    if (q) window.location.href = SUISHOP.root + "?m=My&a=search&q=" + encodeURIComponent(q);
  });

  /* ---------- cart: qty + remove + coupon ---------- */
  $(document).on("click", ".cart-item .minus", function () {
    var $input = $(this).siblings("input.qty-val");
    var v = Math.max(1, (parseInt($input.val(),10)||1) - 1);
    $input.val(v);
    cartUpdate($(this).closest(".cart-item").data("id"), v);
  });
  $(document).on("click", ".cart-item .plus", function () {
    var $input = $(this).siblings("input.qty-val");
    var v = (parseInt($input.val(),10)||1) + 1;
    $input.val(v);
    cartUpdate($(this).closest(".cart-item").data("id"), v);
  });
  $(document).on("change", ".cart-item .qty-val", function () {
    cartUpdate($(this).closest(".cart-item").data("id"), $(this).val());
  });
  function cartUpdate(id, num) {
    $.post(SUISHOP.root + "?m=Cart&a=update", { id: id, number: num }, function (r) {
      if (r.code === 0) {
        $("#sumSubtotal").text(r.subtotal);
        $("#sumTotal").text(r.total);
        $(".cart-point-notice strong").text(r.point + "ポイント");
        $(".cart-count, #cartCount").text(r.count);
      }
    }, "json");
  }

  $(document).on("click", ".cart-remove", function () {
    var $item = $(this).closest(".cart-item");
    var id    = $item.data("id");
    $.post(SUISHOP.root + "?m=Cart&a=remove", { id: id }, function (r) {
      if (r.code === 0) {
        $item.fadeOut(180, function () { $(this).remove(); });
        $("#sumSubtotal").text(r.subtotal);
        $("#sumTotal").text(r.total);
        $(".cart-count, #cartCount").text(r.count);
        if (r.empty) {
          $("#cartLayout").fadeOut(180);
          $("#cartEmpty").fadeIn(180);
        }
      }
    }, "json");
  });

  $(".coupon-row button").on("click", function () {
    var code = $(".coupon-row input").val().trim();
    $.post(SUISHOP.root + "?m=Cart&a=coupon", { code: code }, function (r) {
      if (r.code === 0) {
        toast(r.msg);
        setTimeout(function () { window.location.reload(); }, 600);
      } else {
        toast(r.msg);
      }
    }, "json");
  });

  /* ---------- checkout 选项选择 ---------- */
  $(document).on("click", ".addr-row", function () {
    $(".addr-row").removeClass("selected");
    $(this).addClass("selected");
    $("input[name=address_id]").val($(this).data("id"));
  });
  $(document).on("click", ".pay-option", function () {
    $(".pay-option").removeClass("selected");
    $(this).addClass("selected");
    $("input[name=pay_id]").val($(this).data("id"));
  });
  $(document).on("click", ".shipping-option", function () {
    $(".shipping-option").removeClass("selected");
    $(this).addClass("selected");
    $("input[name=shipping_id]").val($(this).data("id"));
  });

  /* ---------- inquiry chat ---------- */
  $(document).on("click", ".inquiry-select-order", function () {
    $(".inquiry-select-order").removeClass("selected");
    $(this).addClass("selected");
  });
  $(document).on("click", ".inquiry-type-btn", function () {
    $(".inquiry-type-btn").removeClass("selected");
    $(this).addClass("selected");
  });
  $("#startChat").on("click", function () {
    var order = $(".inquiry-select-order.selected").find(".meta strong").text();
    var type  = $(".inquiry-type-btn.selected").text().trim();
    if (!order) { toast("注文を選んでください"); return; }
    var msg = "「" + order + "」の<strong>" + type + "</strong>についてのお問い合わせですね。詳しい状況をお聞かせいただけますか？";
    $(".chat-bubble").first().html("山田様、こんにちは。蔵市カスタマーサポートです。<br>" + msg);
    $("#inquiryStep1").slideUp(200, function () { $("#inquiryStep2").slideDown(200); });
  });
  $("#backToForm").on("click", function () {
    $("#inquiryStep2").slideUp(180, function () { $("#inquiryStep1").slideDown(180); });
  });
  function chatSend() {
    var text = $.trim($("#chatInput").val());
    if (!text) return;
    var now  = new Date();
    var time = now.getHours() + ":" + String(now.getMinutes()).padStart(2, "0");
    var $msg = $('<div class="chat-msg me"><div class="chat-avatar me">私</div><div><div class="chat-bubble"></div><div class="chat-time">' + time + '</div></div></div>');
    $msg.find(".chat-bubble").text(text);
    $("#chatMessages").append($msg);
    $("#chatInput").val("");
    var $cm = $("#chatMessages");
    $cm.scrollTop($cm[0].scrollHeight);
    setTimeout(function () {
      var replies = [
        "ご連絡ありがとうございます。内容を確認し、担当者よりご案内いたします。",
        "詳細をお伺いしました。対応方法について折り返しご連絡いたします。",
        "お手数をおかけいたします。営業時間内に担当者よりご連絡いたします。"
      ];
      var r = replies[Math.floor(Math.random() * replies.length)];
      var t2 = new Date();
      var time2 = t2.getHours() + ":" + String(t2.getMinutes()).padStart(2, "0");
      var $reply = $('<div class="chat-msg"><div class="chat-avatar">蔵</div><div><div class="chat-bubble"></div><div class="chat-time">' + time2 + '</div></div></div>');
      $reply.find(".chat-bubble").text(r);
      $("#chatMessages").append($reply);
      $cm.scrollTop($cm[0].scrollHeight);
    }, 1500);
  }
  $(document).on("click", "#chatSend", chatSend);
  $(document).on("keydown", "#chatInput", function (e) {
    if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); chatSend(); }
  });

  /* ---------- address form 省市区联动 ---------- */
  $(document).on("change", "#selProvince", function () {
    loadArea($(this).val(), "#selCity");
    $("#selArea").html('<option value="">区/町</option>');
  });
  $(document).on("change", "#selCity", function () {
    loadArea($(this).val(), "#selArea");
  });
  function loadArea(parent, target) {
    $.getJSON(SUISHOP.root + "?m=Ajax&a=area", { parent: parent }, function (r) {
      var html = '<option value="">请选择</option>';
      if (r.code === 0 && r.data) {
        r.data.forEach(function (it) { html += '<option value="' + it.id + '">' + it.name + '</option>'; });
      }
      $(target).html(html);
    });
  }

  /* ---------- hero carousel ---------- */
  (function () {
    var $slides = $(".hero-slide");
    if ($slides.length === 0) return;
    var cur = 0;
    var $dots = $(".hero-dots button");
    var total = $slides.length;
    var timer;
    var $hero = $("#heroCarousel");

    function go(n) {
      $slides.eq(cur).removeClass("active");
      $dots.eq(cur).removeClass("active");
      cur = (n + total) % total;
      $slides.eq(cur).addClass("active");
      $dots.eq(cur).addClass("active");
    }
    function autoplay() { timer = setInterval(function () { go(cur + 1); }, 5000); }
    function restart() { clearInterval(timer); autoplay(); }

    $(".hero-next").on("click", function () { go(cur + 1); restart(); });
    $(".hero-prev").on("click", function () { go(cur - 1); restart(); });
    $(".hero-dots button").on("click", function () { go(parseInt($(this).data("slide"), 10)); restart(); });

    /* touch swipe */
    var ts = 0, ty = 0, dx = 0, sw = false;
    $hero.on("touchstart", function (e) {
      var t = e.originalEvent.touches[0];
      ts = t.clientX; ty = t.clientY; dx = 0; sw = true; clearInterval(timer);
    });
    $hero.on("touchmove", function (e) {
      if (!sw) return;
      var t = e.originalEvent.touches[0];
      dx = t.clientX - ts;
      if (Math.abs(dx) > Math.abs(t.clientY - ty)) e.preventDefault();
    });
    $hero.on("touchend", function () {
      if (!sw) return; sw = false;
      if (dx > 40) go(cur - 1); else if (dx < -40) go(cur + 1);
      restart();
    });
    autoplay();
  })();

  /* ---------- gallery lightbox ---------- */
  (function () {
    var $lightbox = $("#lightbox");
    if ($lightbox.length === 0) return;
    var images = [];
    var cur = 0;

    function build() {
      images = [];
      $(".gallery-thumbs button").each(function () {
        images.push({
          src: $(this).find("img").data("full") || $(this).find("img").attr("src"),
          alt: $(this).find("img").attr("alt")
        });
      });
    }
    function show(i) {
      cur = (i + images.length) % images.length;
      $("#lbImg").attr({ src: images[cur].src, alt: images[cur].alt });
      $("#lbCaption").text((cur + 1) + " / " + images.length + "  " + images[cur].alt);
    }
    function open(i) {
      build(); show(i); $lightbox.addClass("open"); $("body").css("overflow", "hidden");
    }
    function close() { $lightbox.removeClass("open"); $("body").css("overflow", ""); }
    $(document).on("click", "#galleryMain img", function () {
      var idx = $(".gallery-thumbs button.active").index();
      open(idx >= 0 ? idx : 0);
    });
    $("#lbClose, #lightbox").on("click", function (e) { if (e.target === this) close(); });
    $("#lbPrev").on("click", function (e) { e.stopPropagation(); show(cur - 1); });
    $("#lbNext").on("click", function (e) { e.stopPropagation(); show(cur + 1); });
    $(document).on("keydown", function (e) {
      if (!$lightbox.hasClass("open")) return;
      if (e.key === "ArrowLeft") show(cur - 1);
      if (e.key === "ArrowRight") show(cur + 1);
      if (e.key === "Escape") close();
    });
  })();

  /* ---------- toast ---------- */
  window.toast = function (msg) {
    var $t = $('<div class="suishop-toast">' + msg + '</div>');
    $("body").append($t);
    setTimeout(function () { $t.addClass("show"); }, 10);
    setTimeout(function () {
      $t.removeClass("show");
      setTimeout(function () { $t.remove(); }, 300);
    }, 2200);
  };

  /* ---------- 搜索建议（auto） ---------- */
  var sugTimer;
  $(document).on("input", "#searchInput", function () {
    clearTimeout(sugTimer);
    var q = $(this).val().trim();
    if (!q) { $("#searchSuggest").hide(); return; }
    sugTimer = setTimeout(function () {
      $.getJSON(SUISHOP.root + "?m=Ajax&a=suggest", { q: q }, function (r) {
        if (r.code !== 0 || !r.data || !r.data.length) { $("#searchSuggest").hide(); return; }
        var html = r.data.map(function (it) {
          return '<a href="' + it.url + '"><img src="' + it.thumb_url + '"><div><strong>' + it.title + '</strong><span>' + it.price + '</span></div></a>';
        }).join("");
        $("#searchSuggest").html(html).show();
      });
    }, 250);
  });
  $(document).on("click", function (e) {
    if (!$(e.target).closest(".search-form").length) $("#searchSuggest").hide();
  });

});

/* ============================================================
 * Global: toast CSS inject
 * ============================================================ */
(function () {
  if (document.getElementById("suishop-toast-style")) return;
  var s = document.createElement("style");
  s.id = "suishop-toast-style";
  s.textContent =
    ".suishop-toast{position:fixed;left:50%;top:24px;transform:translate(-50%,-10px);" +
    "background:rgba(29,29,31,.92);color:#fff;padding:10px 18px;border-radius:980px;" +
    "font-size:13px;z-index:9999;opacity:0;transition:.25s;backdrop-filter:blur(8px);" +
    "box-shadow:0 6px 24px rgba(0,0,0,.18)}" +
    ".suishop-toast.show{opacity:1;transform:translate(-50%,0)}" +
    ".search-suggest{position:absolute;left:0;right:0;top:100%;background:#fff;border:1px solid var(--line, #D2D2D7);border-radius:12px;box-shadow:0 12px 30px rgba(0,0,0,.12);display:none;z-index:30;overflow:hidden;max-height:380px;overflow-y:auto}" +
    ".search-suggest a{display:flex;gap:10px;padding:10px 14px;align-items:center;border-bottom:1px solid var(--line-soft, #E8E8ED)}" +
    ".search-suggest a:last-child{border-bottom:none}" +
    ".search-suggest a img{width:46px;height:46px;object-fit:cover;border-radius:8px;flex-shrink:0}" +
    ".search-suggest a strong{display:block;font-size:13.5px;font-weight:500;color:var(--ink, #1D1D1F)}" +
    ".search-suggest a span{font-size:12.5px;color:var(--red, #FF3B30);font-weight:600}" +
    ".search-suggest a:hover{background:var(--bg-alt, #F5F5F7)}" +
    ".search-form{position:relative}";
  document.head.appendChild(s);
})();