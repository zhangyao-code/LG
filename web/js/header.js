$(function ($) {

    //header
    //$(window).scroll(function () {
    //    if ($(window).scrollTop() > 100) {
    //        $('.header').addClass('active');
    //    } else {
    //        $('.header').removeClass('active');
    //    }
    //});



    //header�����˵�
    (function () {
        if ($(window).width() > 1000) {
            $('.header .head-nav .navul li').hover(function () {
                $(this).find('.select').stop(true, true).slideDown();
                $(this).find('.ones').addClass('hover');
            }, function () {
                $(this).find('.select').stop(true, true).slideUp();
                $(this).find('.ones').removeClass('hover');
            });
        } else {
            $('.navul li').each(function () {
                var _this = $(this);
                _this.attr('data-open', 'false');
            });
            $('.navul li').bind('click', function () {
                $(".navul li .plus").removeClass("bold");
                var _this = $(this), _dataOpen = _this.attr('data-open');
                if (_dataOpen == 'false') {
                    _this.find('.plus').addClass("bold");
                    $('.navul li .select').slideUp(500);
                    $('.navul li').attr('data-open', 'false');
                    $('.select', _this).slideDown(500);
                    _this.attr('data-open', 'true');
                } else {
                    $('.select', _this).slideUp(500);
                    _this.attr('data-open', 'false');
                }
            });
            $('.select,.navul li .ones')
                .bind('click',
                function (event) {
                    event.stopPropagation();
                });
        }



        $(".item-info").hide();
        $('.feature-list .item').hover(
               function () {
                   $(this).find(".item-info").fadeTo("slow", 1);
               },
               function () {
                   $(this).find(".item-info").hide();
               });




    })();

    //footer�ײ���ά��
    (function () {
        $('.footer .footr div').hover(function () {
            $('.foot_wechat').fadeIn();
        }, function () {
            $('.foot_wechat').fadeOut();
        });
    })();


});
