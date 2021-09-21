define(function(require, exports, module) {

	exports.run = function() {

		$(".delete-btn").on('click', function(){
			if (!confirm(Translator.trans('admin.content.delete_hint'))) {
				return ;
			}
			$.post($(this).data('url'), function(){
				window.location.reload();
			});
		});
    $('.es-switch').on('click' , function (e) {
      let self =this;
      let home = 0;
      if($(self).hasClass('is-active')){
        home = 0;
      }else{
        home = 1;
      }

      $.post($(self).data('url'),{'home':home}, function(){
        var $input = $(self).find('.es-switch__input');
        var ToggleVal = $input.val() == $input.data('open')? $input.data('close') : $input.data('open');
        $input.val(ToggleVal);
        $(self).toggleClass('is-active');
      });
    });
	};

});