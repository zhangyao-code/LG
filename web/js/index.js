var main = {};

(function ($) {
    var swiper2;
    var navPosition = '120px 42px';

    main.init = function () {
        main.initEvent();

        initSwiper();		//配置轮播图
        ani1();				//全球领先的追溯解决方案动画
        compatibilityIE();	//样式兼容ie
    };

    main.initEvent = function () {
        /**
         * @desc 监听滚动条，添加动画
         */
        //$(document).on('scroll', scrollAni);

       
        $('.caseBox').on('click', '.prev', function () {
            swiper2.swipePrev();
        });

        $('.caseBox').on('click', '.next', function () {
            swiper2.swipeNext();
        });
    };

    /**
     * @desc 配置轮播图
     */
    function initSwiper() {
        //banner
        new Swiper('.swiper-container', {
            pagination: '.pagination',
            loop: true,
            calculateHeight: true,
            paginationClickable: true,
            autoplayDisableOnInteraction: false,
            autoplay: 3000
        });

        $('.swiper-container').css({
            'opacity': '1',
            'filter': 'alpha(opacity=100)'
        });

        //精品案例轮播图
        swiper2 = new Swiper('.swiper-container2', {
            slidesPerView: 5,
            loop: true,
            calculateHeight: true,
            paginationClickable: true
        });
    }

    /**
     * @desc 全球领先的追溯解决方案动画
     */
    function ani1() {
        $('.soluteList > .li').on('mouseover', function () {
            $(this).addClass('active').siblings().removeClass('active');
        });
    }

    /**
     * @desc 样式兼容ie
     */
    function compatibilityIE() {
        if (navigator.appName == 'Microsoft Internet Explorer' && parseInt(navigator.appVersion.split(/msie/i)[1]) <= 9) {
            $('.individuation .imgBox img').css('opacity', 1).css('filter', 'alpha(opacity=100)');
            $('.individuation .imgBox ul').css('opacity', 1);
        }
    }

    /**
     * @desc 监听滚动条添加动画
     */
    function scrollAni() {
        var scrollTop = $(window).scrollTop();
        var showTop = $('.show .li .img').offset().top;
        var individuationTop = $('.individuation .imgBox').offset().top;

        //$('.show .li .img')动画
        if (scrollTop > showTop - $(window).height() && scrollTop < showTop + $('.show .li .img').height()) {
            $('.show .li .img').addClass('ani2');
        }
        else {
            $('.show .li .img').removeClass('ani2');
        }

        //$('.individuation .imgBox')动画
        if (scrollTop > individuationTop - $(window).height() && scrollTop < individuationTop + $('.individuation .imgBox').height()) {

            $('.individuation .imgBox img').addClass('ani3');
            $('.individuation .imgBox ul').addClass('ani4');
        }
        else {
            $('.individuation .imgBox img').removeClass('ani3');
            $('.individuation .imgBox ul').removeClass('ani4');
        }
    }

})(jQuery);

window.main = main;