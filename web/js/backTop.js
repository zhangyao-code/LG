/**
 * @desc 回到顶部组件      ---兼容性（兼容至IE7）
 * @param cfg {object} 参数
 * @param cfg.container {string} 回到顶部的按钮
 * @param cfg.scrollTarget {string} 滚动条回到的位置（默认为顶部0px）
 * @param cfg.scrollTime {number} 回到指定位置所需时间
 * @param cfg.scrollEachlength {number} 每次滚动的距离
 * @param cfg.distanceShow {number} 滚动至多少高度时容器显示（默认为300）
 */

var backTop = function(cfg) {
	var self = this;

	self.container = cfg.container;
	self.scrollTarget = cfg.scrollTarget || "0px";
	self.scrollTime = cfg.scrollTime;
    self.scrollEachlength = cfg.scrollEachlength;
    self.distanceShow = cfg.distanceShow || 300;

	self.init();
};

(function($) {
	backTop.prototype.init = function() {
		var self = this;

		window.onscroll = function() {
			if($(window).scrollTop() > self.distanceShow) {
				$(self.container).show().css('display', 'block');
			}else {
				$(self.container).hide();
			}
		};

		$(self.container).on("click",function() {
			self.startScroll(self.scrollTarget,self.scrollTime);
		});
	};

	backTop.prototype.startScroll = function(scrollTo, time) {
		var self = this;

		var scrollFrom = parseInt(document.body.scrollTop) || parseInt(document.documentElement.scrollTop);
		var i = 0;
		scrollTo = parseInt(scrollTo);
		time /= self.scrollEachlength;

		var interval = setInterval(function () {
			i++;

			document.body.scrollTop = document.documentElement.scrollTop = (scrollTo - scrollFrom) / time * i + scrollFrom;

			if (i >= time) {
				clearInterval(interval);
			}
		}, self.scrollEachlength);
	}

})(jQuery);

window.backTop = backTop;
