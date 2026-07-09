$(function () {
  /* ---------- mobile nav toggle ---------- */
  $(".menu-toggle").on("click", function () {
    $(".main-nav").toggleClass("open");
    $(this).attr("aria-expanded", $(".main-nav").hasClass("open"));
  });
  /* ---------- favorite (heart) toggle ---------- */
  $(document).on("click", ".fav-btn", function (e) {
    e.preventDefault();
    $(this).toggleClass("active");
  });
  /* ---------- quantity stepper ---------- */
  $(document).on("click", ".qty-stepper .minus", function () {
    var $input = $(this).siblings("input");
    var val = Math.max(1, (parseInt($input.val(), 10) || 1) - 1);
    $input.val(val);
  });
  $(document).on("click", ".qty-stepper .plus", function () {
    var $input = $(this).siblings("input");
    var val = (parseInt($input.val(), 10) || 1) + 1;
    $input.val(val);
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
  /* ---------- mypage left nav active state by page ---------- */
  var page = $("body").data("page");
  if (page) {
    $(".mypage-nav a[data-page='" + page + "']").addClass("active");
    $(".main-nav a[data-page='" + page + "']").addClass("active");
  }
  /* ---------- simple front-end validation: login ---------- */
  $("#loginForm").on("submit", function (e) {
    e.preventDefault();
    var ok = true;
    var $email = $("#loginEmail");
    var $pass = $("#loginPassword");
    if (!$email.val() || $email.val().indexOf("@") === -1) {
      $email.closest(".field").addClass("has-error");
      ok = false;
    } else {
      $email.closest(".field").removeClass("has-error");
    }
    if (!$pass.val() || $pass.val().length < 4) {
      $pass.closest(".field").addClass("has-error");
      ok = false;
    } else {
      $pass.closest(".field").removeClass("has-error");
    }
    if (ok) {
      window.location.href = "mypage.html";
    }
  });
  /* ---------- simple front-end validation: register ---------- */
  $("#registerForm").on("submit", function (e) {
    e.preventDefault();
    var ok = true;
    $(this).find("input[required]").each(function () {
      var $f = $(this).closest(".field");
      if (!$(this).val()) {
        $f.addClass("has-error");
        ok = false;
      } else {
        $f.removeClass("has-error");
      }
    });
    var pass = $("#regPassword").val();
    var confirm = $("#regPasswordConfirm").val();
    if (pass && confirm && pass !== confirm) {
      $("#regPasswordConfirm").closest(".field").addClass("has-error");
      $("#regPasswordConfirm").siblings(".field-error").text("パスワードが一致しません");
      ok = false;
    }
    if (ok) {
      $("#registerForm").hide();
      $("#registerSuccess").fadeIn(200);
    }
  });
  /* ---------- list page: price slider sync (basic) ---------- */
  $("#sortSelect").on("change", function () {
    // demo only: visual feedback that sort was applied
    $(".list-toolbar .count").fadeOut(120).fadeIn(120);
  });
  /* ---------- list filter chip removal ---------- */
  $(document).on("click", ".tag-chip button", function () {
    $(this).closest(".tag-chip").fadeOut(150, function () {
      $(this).remove();
    });
  });
  /* ---------- header search demo ---------- */
  $(".search-form").on("submit", function (e) {
    e.preventDefault();
    var q = $(this).find("input").val();
    if (q) window.location.href = "list.html?q=" + encodeURIComponent(q);
  });
});
(function(window,$){
    if($("#ui-message").length==0){
        $("body").append('<div id="ui-message"></div>');
    }
    window.message=function(text,type,time){
        type=type||'success';
        time=time||2000;
        var icon='';
        switch(type){
            case 'success':
                icon='✓';
                break;
            case 'error':
                icon='✕';
                break;
            case 'warning':
                icon='!';
                break;
            case 'info':
                icon='i';
                break;
            default:
                icon='';
        }
        var html='';
        if(icon!=''){
            html+='<span class="icon">'+icon+'</span>';
        }
        html+='<span class="text">'+text+'</span>';
        var box=$("#ui-message");
        box.removeClass()
           .addClass(type)
           .html(html)
           .stop(true,true)
           .fadeIn(180);
        clearTimeout(window.messageTimer);
        window.messageTimer=setTimeout(function(){
            box.fadeOut(180);
        },time);
    };
})(window,jQuery);