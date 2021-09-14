define(function(require, exports, module) {

    var WebUploader = require('edusoho.webuploader');
    var Notify = require('common/bootstrap-notify');
    var Uploader = require('upload');

    exports.run = function() {
        let $form = $("#swiper-form");
        let $soluteFrom = $('#solute-form')
      let $traceForm = $('#trace-form');
        let $detailForm = $('#detail-form');
        let upload1 = new WebUploader({
            element: '#js-swiper1'
          });
        let upload2 = new WebUploader({
          element: '#js-swiper2'
        });
        let upload3 = new WebUploader({
          element: '#js-swiper3'
        });
        let upload4 = new WebUploader({
          element: '#js-swiper4'
        });
        let upload5 = new WebUploader({
          element: '#js-swiper5'
        });
        let upload6 = new WebUploader({
          element: '#soluteList1-icon1'
        });
        let upload7 = new WebUploader({
          element: '#soluteList1-icon2'
        });
        let upload8 = new WebUploader({
          element: '#soluteList2-icon1'
        });
        let upload9 = new WebUploader({
          element: '#soluteList2-icon2'
        });
        let upload10 = new WebUploader({
          element: '#soluteList3-icon1'
        });
        let upload11 = new WebUploader({
          element: '#soluteList3-icon2'
        });
        let upload12 = new WebUploader({
          element: '#soluteList4-icon1'
        });
        let upload13 = new WebUploader({
          element: '#soluteList4-icon2'
        });
        let upload14 = new WebUploader({
          element: '#traceImage'
        });
        let upload15 = new WebUploader({
          element: '#traceIcon1'
        });
        let upload16 = new WebUploader({
          element: '#traceIcon2'
        });
        let upload17 = new WebUploader({
          element: '#traceIcon3'
        });
        let upload18 = new WebUploader({
          element: '#traceIcon4'
        });
        let upload19 = new WebUploader({
          element: '#traceIcon5'
        });
        let upload20 = new WebUploader({
          element: '#traceIcon6'
        });
        let upload21 = new WebUploader({
          element: '#traceIcon7'
        });
        let upload22 = new WebUploader({
          element: '#js-detail'
        });

      upload22.on('uploadSuccess', function(file, response ) {
        let  url = $("#js-detail").data("gotoUrl");
        $.post(url, response ,function(data){
          $("#js-detail").css('background',`url(${data['url']}) center no-repeat`);
          $("#js-detail").find('.js-swiper-value').val(data.path);
          Notify.success(Translator.trans('上传图片成功！'));
        });
      });

        uploadSuccess(upload1);
        uploadSuccess(upload2);
        uploadSuccess(upload3);
        uploadSuccess(upload4);
        uploadSuccess(upload5);
        uploadSuccess(upload6);
        uploadSuccess(upload7);
        uploadSuccess(upload8);
        uploadSuccess(upload9);
        uploadSuccess(upload10);
        uploadSuccess(upload11);
        uploadSuccess(upload12);
        uploadSuccess(upload13);
        uploadSuccess(upload14);
        uploadSuccess(upload15);
        uploadSuccess(upload16);
        uploadSuccess(upload17);
        uploadSuccess(upload18);
        uploadSuccess(upload19);
        uploadSuccess(upload20);
        uploadSuccess(upload21);


      function uploadSuccess($event){

        $event.on('uploadSuccess', function(file, response ) {
          let parent = $event.element.parents('.img-group');
          let  url = $(".js-swiper").data("gotoUrl");
          $.post(url, response ,function(data){
              if(parent.find('.site-logo-container')){
                parent.find(".site-logo-container").html('<img src="' + data.url + '">');
              }else{
                parent.find(".webuploader-pick").html('<img src="' + data.url + '">');
              }
              parent.find('.js-swiper-value').val(data.path);
              parent.find(".js-swiper-delete").removeClass('hidden');
              Notify.success(Translator.trans('上传图片成功！'));
          });
        });
      }

      $('.js-swiper-delete').on('click', function(){
         let parent = $(this).parents('.form-group');
        parent.find(".site-logo-container").html('');
        parent.find('.js-swiper-value').val('');
        parent.find(".js-swiper-delete").addClass('hidden');
      })

      $('#save-swiper').on('click', function(){
        $.post($form.data('saveUrl'), $form.serialize(), function(data){
          Notify.success('设置成功');
        })
      })
      $('#save-swiper').on('click', function(){
        $.post($form.data('saveUrl'), $form.serialize(), function(data){
          Notify.success('设置成功');
        })
      })
      $('#save-solute').on('click', function(){
        $.post($soluteFrom.data('saveUrl'), $soluteFrom.serialize(), function(data){
          Notify.success('设置成功');
        })
      })
      $('#save-trace').on('click', function(){
        $.post($traceForm.data('saveUrl'), $traceForm.serialize(), function(data){
          Notify.success('设置成功');
        })
      })
      $('#save-detail').on('click', function(){
        $.post($detailForm.data('saveUrl'), $detailForm.serialize(), function(data){
          Notify.success('设置成功');
        })
      })

    };


});
