!function(l){function t(t){for(var e,n,i=t[0],o=t[1],r=t[2],s=0,a=[];s<i.length;s++)n=i[s],Object.prototype.hasOwnProperty.call(c,n)&&c[n]&&a.push(c[n][0]),c[n]=0;for(e in o)Object.prototype.hasOwnProperty.call(o,e)&&(l[e]=o[e]);for(h&&h(t);a.length;)a.shift()();return d.push.apply(d,r||[]),u()}function u(){for(var t,e=0;e<d.length;e++){for(var n=d[e],i=!0,o=1;o<n.length;o++){var r=n[o];0!==c[r]&&(i=!1)}i&&(d.splice(e--,1),t=s(s.s=n[0]))}return t}var n={},c={91:0},d=[];function s(t){if(n[t])return n[t].exports;var e=n[t]={i:t,l:!1,exports:{}};return l[t].call(e.exports,e,e.exports,s),e.l=!0,e.exports}s.m=l,s.c=n,s.d=function(t,e,n){s.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:n})},s.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},s.t=function(e,t){if(1&t&&(e=s(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var n=Object.create(null);if(s.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var i in e)s.d(n,i,function(t){return e[t]}.bind(null,i));return n},s.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return s.d(e,"a",e),e},s.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},s.p="/static-dist/";var e=window.webpackJsonp=window.webpackJsonp||[],i=e.push.bind(e);e.push=t,e=e.slice();for(var o=0;o<e.length;o++)t(e[o]);var h=i;d.push([736,0]),u()}({106:function(t,e,n){"use strict";var i=n(6),s=n.n(i),o=n(0),r=n.n(o),a=n(1),l=n.n(a),u=n(2),c=n.n(u),d=n(20),h=n.n(d),p=n(7),f=n.n(p),y=n(4),g=n.n(y),m=n(8),v=n.n(m),w=n(11),_=(n(153),n(65)),k=n(61);function b(i){return function(){var t,e=g()(i);if(function(){if("undefined"==typeof Reflect||!c.a)return;if(c.a.sham)return;if("function"==typeof Proxy)return 1;try{return Date.prototype.toString.call(c()(Date,[],function(){})),1}catch(t){return}}()){var n=g()(this).constructor;t=c()(e,arguments,n)}else t=e.apply(this,arguments);return f()(this,t)}}var T=function(t){v()(i,t);var n=b(i);function i(t){var e;return r()(this,i),(e=n.call(this))._options=t,e._initConfig(),e.chapterAnimate(),e._displayAllImmediately?e._displayCurrentPageDataAndSwitchToNext():e._initUpLoading(),e}return l()(i,[{key:"toggleIcon",value:function(i,o,r){var s=this;return new h.a(function(t,e){var n=i.find(".js-remove-icon");i.find(".js-remove-text");n.hasClass(o)?(n.removeClass(o).addClass(r),0==$(".js-only-display-one-page").length&&s._displayCurrentPageDataAndSwitchToNext()):n.removeClass(r).addClass(o),t()})}},{key:"chapterAnimate",value:function(t,e,n,i){var o=1<arguments.length&&void 0!==e?e:".js-task-chapter",r=2<arguments.length&&void 0!==n?n:"es-icon-remove",s=3<arguments.length&&void 0!==i?i:"es-icon-anonymous-iconfont",a=this;$(0<arguments.length&&void 0!==t?t:"body").off("click").on("click",o,function(t){var e=$(t.currentTarget);a.toggleIcon(e,r,s).then(function(){e.nextUntil(o).animate({height:"toggle",opacity:"toggle"},"normal")})})}},{key:"_initUpLoading",value:function(){if(0!=$(".js-down-loading-more").length)var e=this,n=new Waypoint({element:$(".js-down-loading-more")[0],handler:function(t){"down"==t&&(e._isLastPage||e._canNotDisplayMore()?n.disable():(e._scrollToBottom(),n.disable(),e._displayCurrentPageDataAndSwitchToNext(),Waypoint.refreshAll(),n.enable()))},offset:"bottom-in-view"})}},{key:"_initConfig",value:function(){this._currentPage=1,this._displayAllImmediately=!!this._options.displayAllImmediately,this._displayAllImmediately?this._pageSize=1e4:this._pageSize=this._options.pageSize?this._options.pageSize:25,25<this._pageSize&&0!=$(".js-only-display-one-page").length&&(this._pageSize=25),this._afterFirstLoad=this._options.afterFirstLoad?this._options.afterFirstLoad:null,this._isFirstLoad=!0,this._options.displayItem?(this._displayItemDisplayed=!1,this._displayItem=this._options.displayItem):(this._displayItemDisplayed=!0,this._displayItem=null),this._isLastPage=!1}},{key:"_displayCurrentPageDataAndSwitchToNext",value:function(){this._displayData(),this._isLastPage||this._currentPage++,this._isFirstLoad&&(this._displayItemDisplayed?(this._isFirstLoad=!1,this._afterFirstLoad&&this._afterFirstLoad()):this._displayCurrentPageDataAndSwitchToNext())}},{key:"_displayData",value:function(){if(!this._isLastPage)for(var t=this._getStartIndex(),e=0;e<this._pageSize;e++){var n=this._options.data[e+t];if(!this._displayItemDisplayed){var i=this._displayItem.key,o=this._displayItem.value;n[i]==o&&(this._displayItemDisplayed=!0)}Object(w.d)(n)?this._isLastPage=!0:this._generateSingleCachedData(n)}}},{key:"_scrollToBottom",value:function(){var t=this,e=this,n=$(".js-sidebar-pane");if(n.length){var i=n[0],o=n.height(),r=i.scrollHeight,s=i.scrollTop;e._afterFirstLoad&&i.addEventListener("scroll",Object(k.a)(function(){r<=s+o&&!t._isLastPage&&e._displayCurrentPageDataAndSwitchToNext()},500,!0))}}},{key:"_generateSingleCachedData",value:function(t){var e=this._options.dataTemplateNode,n=this._options.wrapDom?this._options.wrapDom.find(e).html():$(e).html(),i=t,o=this,r=n.replace(/({\w+})/g,function(t){return o._replace(t,i,"{","}")});r=r.replace(/(%7B\w+%7D)/g,function(t){return o._replace(t,i,"%7B","%7D")});var s=$("<div>").append(r);this._removeUnNeedNodes(s),(this._options.wrapDom?this._options.wrapDom.find(".infinite-container"):$(".infinite-container")).append(s.html())}},{key:"_getStartIndex",value:function(){return(this._currentPage-1)*this._pageSize}},{key:"_replace",value:function(t,e,n,i){var o=t.split(n)[1].split(i)[0],r=this._options.context;return"function"==typeof r[o]?r[o](e,r):void 0!==e[o]?e[o]:t}},{key:"_canNotDisplayMore",value:function(){return 1!=this._currentPage&&0!=$(".js-only-display-one-page").length}},{key:"_removeUnNeedNodes",value:function(t){t.find("[display-if=false]").remove(),t.find("[display-if=0]").remove(),t.find("[hide-if=1]").remove(),t.find("[hide-if=true]").remove(),t.find("tmp :first-child").each(function(){var t=$(this).parent();t.hasClass("js-ignore-remove")||"TMP"!=t[0].nodeName||$(this).unwrap()})}}]),i}(_.a),S=function(){function e(t){r()(this,e),void 0===t&&(t={}),this._init(t)}return l()(e,[{key:"_init",value:function(t){var e=$.extend(this._getDefaultOptions(t),t);e.wrapDom=t.wrapTarget,e.pageSize=this._getPageSizeByMaxLessonsNumOfChapter(e),new T(e),this._displayAllImmediately&&this._destroyPaging()}},{key:"_getPageSizeByMaxLessonsNumOfChapter",value:function(e){var t=e.data;if(!Object(w.d)(t)){var n=0,i=0;return t.forEach(function(t){e.context.isChapter(t)?(n=n<i?i:n,i=0):i++}),n<25?25:n+1}}},{key:"_getDefaultOptions",value:function(t){var e=this._wrapTarget(t.wrapTarget,".js-hidden-cached-data"),n=this._wrapTarget(t.wrapTarget,".js-hidden-course-info"),i=this._wrapTarget(t.wrapTarget,".js-hidden-i18n"),o=this._wrapTarget(t.wrapTarget,".js-hidden-activity-metas"),r=this._wrapTarget(t.wrapTarget,".js-hidden-current-timestamp");return{data:this._toJson(e.html()),context:{course:this._toJson(n.html()),i18n:this._toJson(i.html()),metas:this._toJson(o.html()),currentTimeStamp:s()(r.html(),10),isChapter:function(t){return"chapter"==t.itemType},isUnit:function(t){return"unit"==t.itemType},isLesson:function(t){return"lesson"==t.itemType},isTask:function(t){return"task"==t.itemType},getChapterName:function(t,e){return Translator.trans("course.chapter",{chapter_name:e.i18n.i18nChapterName,number:t.number,title:t.title,colon:t.title?":":""})},getUnitName:function(t,e){return Translator.trans("course.unit",{part_name:e.i18n.i18nUnitName,number:t.number,title:t.title,colon:t.title?":":""})},getLessonName:function(t,e){return"1"==t.isOptional?t.title:Translator.trans("course.lesson",{part_name:e.i18n.i18nLessonName,number:e.getLessonNum(t,e),title:t.title})},isItemDisplayedAsOptionalOrUnpublished:function(t,e){return e.isItemDisplayedAsOptional(t,e)||e.isItemDisplayedAsUnpublished(t,e)},isItemDisplayedAsOptional:function(t,e){return"1"==t.isOptional&&e.isLessonNode(t,e)},isItemDisplayedAsUnpublished:function(t,e){return!e.isPublished(t,e)&&e.isLessonNode(t,e)},isLessonNode:function(t){return"task"==t.itemType&&t.isSingleTaskLesson||"lesson"==t.itemType&&!t.isSingleTaskLesson},getTaskName:function(t,e){return t.isSingleTaskLesson?Translator.trans("course.lesson",{part_name:e.i18n.i18nLessonName,number:e.getLessonNum(t,e),title:t.title}):Translator.trans("course.catalogue.task_status.task",{taskName:e.i18n.i18nTaskName,taskNumber:t.number,taskTitle:t.title})},hasWatchLimitRemaining:function(t){return!1!==t.watchLimitRemaining},highlightTaskClass:function(t,e){return t.taskId==e.course.currentTaskId?"active":""},taskClass:function(t,e){var n="es-icon left-menu";return e.isTaskLocked(t,e)?n+=" es-icon-lock":""==t.result||"false"==e.course.isMember?n+=" es-icon-undone-check color-gray":"start"==t.resultStatus?n+=" es-icon-doing color-primary":"finish"==t.resultStatus&&(n+=" es-icon-iccheckcircleblack24px color-primary"),n},lessonContainerClass:function(t,e){var n="color-gray bg-gray-lighter";return e.isTask(t,e)?t.isSingleTaskLesson?n:"":e.isLesson(t,e)?n:void 0},isTaskLocked:function(t,e){return e.course.isMember?"lockMode"==e.course.learnMode&&t.lock:"lockMode"==e.course.learnMode},isPublished:function(t){return"published"==t.status},isPublishedTaskUnlocked:function(t,e){return e.isPublished(t,e)&&!e.isTaskLocked(t,e)},isCloudVideo:function(t){return"video"==t.type&&"cloud"==t.fileStorage},getMetaIcon:function(t,e){return void 0!==e.metas[t.type]?e.metas[t.type].icon:""},getMetaName:function(t,e){return void 0!==e.metas[t.type]?e.metas[t.type].name:""},isLiveReplayGenerated:function(t){return"ungenerated"!=t.replayStatus},isLive:function(t){return"live"==t.type},isLiveNotStarted:function(t,e){return e.isLive(t,e)&&e.currentTimeStamp<e.toInt(t.activityStartTime)},isLiveStarting:function(t,e){return e.isLive(t,e)&&e.currentTimeStamp>=e.toInt(t.activityStartTime)&&e.currentTimeStamp<=e.toInt(t.activityEndTime)},isLiveFinished:function(t,e){return e.isLive(t,e)&&e.currentTimeStamp>e.toInt(t.activityEndTime)},toInt:function(t){return s()(t,10)},getLessonNum:function(t,e){var n=t.number;return"1"==e.course.isHideUnpublish&&(n=t.published_number),n}},dataTemplateNode:".js-infinite-item-template"}}},{key:"_wrapTarget",value:function(t,e){return t?t.find(e):$(e)}},{key:"_destroyPaging",value:function(){for(var t=["js-infinite-item-template","js-hidden-cached-data","js-hidden-course-info","js-hidden-i18n","js-hidden-activity-metas","js-hidden-current-timestamp","infinite-container","js-down-loading-more"],e=0;e<t.length;e++)$("."+t[e]).removeClass(t[e])}},{key:"_toJson",value:function(t){var e={};return t&&(e=$.parseJSON(t.replace(/[\r\n\t]/g,""))),e}}]),e}();e.a=S},153:function(t,e){
/*!
Waypoints - 4.0.1
Copyright © 2011-2016 Caleb Troughton
Licensed under the MIT license.
https://github.com/imakewebthings/waypoints/blob/master/licenses.txt
*/
!function(){"use strict";function e(t){if(!t)throw new Error("No options passed to Waypoint constructor");if(!t.element)throw new Error("No element option passed to Waypoint constructor");if(!t.handler)throw new Error("No handler option passed to Waypoint constructor");this.key="waypoint-"+n,this.options=e.Adapter.extend({},e.defaults,t),this.element=this.options.element,this.adapter=new e.Adapter(this.element),this.callback=t.handler,this.axis=this.options.horizontal?"horizontal":"vertical",this.enabled=this.options.enabled,this.triggerPoint=null,this.group=e.Group.findOrCreate({name:this.options.group,axis:this.axis}),this.context=e.Context.findOrCreateByElement(this.options.context),e.offsetAliases[this.options.offset]&&(this.options.offset=e.offsetAliases[this.options.offset]),this.group.add(this),this.context.add(this),r[this.key]=this,n+=1}var n=0,r={};e.prototype.queueTrigger=function(t){this.group.queueTrigger(this,t)},e.prototype.trigger=function(t){this.enabled&&this.callback&&this.callback.apply(this,t)},e.prototype.destroy=function(){this.context.remove(this),this.group.remove(this),delete r[this.key]},e.prototype.disable=function(){return this.enabled=!1,this},e.prototype.enable=function(){return this.context.refresh(),this.enabled=!0,this},e.prototype.next=function(){return this.group.next(this)},e.prototype.previous=function(){return this.group.previous(this)},e.invokeAll=function(t){var e=[];for(var n in r)e.push(r[n]);for(var i=0,o=e.length;i<o;i++)e[i][t]()},e.destroyAll=function(){e.invokeAll("destroy")},e.disableAll=function(){e.invokeAll("disable")},e.enableAll=function(){for(var t in e.Context.refreshAll(),r)r[t].enabled=!0;return this},e.refreshAll=function(){e.Context.refreshAll()},e.viewportHeight=function(){return window.innerHeight||document.documentElement.clientHeight},e.viewportWidth=function(){return document.documentElement.clientWidth},e.adapters=[],e.defaults={context:window,continuous:!0,enabled:!0,group:"default",horizontal:!1,offset:0},e.offsetAliases={"bottom-in-view":function(){return this.context.innerHeight()-this.adapter.outerHeight()},"right-in-view":function(){return this.context.innerWidth()-this.adapter.outerWidth()}},window.Waypoint=e}(),function(){"use strict";function e(t){window.setTimeout(t,1e3/60)}function n(t){this.element=t,this.Adapter=g.Adapter,this.adapter=new this.Adapter(t),this.key="waypoint-context-"+i,this.didScroll=!1,this.didResize=!1,this.oldScroll={x:this.adapter.scrollLeft(),y:this.adapter.scrollTop()},this.waypoints={vertical:{},horizontal:{}},t.waypointContextKey=this.key,o[t.waypointContextKey]=this,i+=1,g.windowContext||(g.windowContext=!0,g.windowContext=new n(window)),this.createThrottledScrollHandler(),this.createThrottledResizeHandler()}var i=0,o={},g=window.Waypoint,t=window.onload;n.prototype.add=function(t){var e=t.options.horizontal?"horizontal":"vertical";this.waypoints[e][t.key]=t,this.refresh()},n.prototype.checkEmpty=function(){var t=this.Adapter.isEmptyObject(this.waypoints.horizontal),e=this.Adapter.isEmptyObject(this.waypoints.vertical),n=this.element==this.element.window;t&&e&&!n&&(this.adapter.off(".waypoints"),delete o[this.key])},n.prototype.createThrottledResizeHandler=function(){function t(){e.handleResize(),e.didResize=!1}var e=this;this.adapter.on("resize.waypoints",function(){e.didResize||(e.didResize=!0,g.requestAnimationFrame(t))})},n.prototype.createThrottledScrollHandler=function(){function t(){e.handleScroll(),e.didScroll=!1}var e=this;this.adapter.on("scroll.waypoints",function(){e.didScroll&&!g.isTouch||(e.didScroll=!0,g.requestAnimationFrame(t))})},n.prototype.handleResize=function(){g.Context.refreshAll()},n.prototype.handleScroll=function(){var t={},e={horizontal:{newScroll:this.adapter.scrollLeft(),oldScroll:this.oldScroll.x,forward:"right",backward:"left"},vertical:{newScroll:this.adapter.scrollTop(),oldScroll:this.oldScroll.y,forward:"down",backward:"up"}};for(var n in e){var i=e[n],o=i.newScroll>i.oldScroll?i.forward:i.backward;for(var r in this.waypoints[n]){var s=this.waypoints[n][r];if(null!==s.triggerPoint){var a=i.oldScroll<s.triggerPoint,l=i.newScroll>=s.triggerPoint;(a&&l||!a&&!l)&&(s.queueTrigger(o),t[s.group.id]=s.group)}}}for(var u in t)t[u].flushTriggers();this.oldScroll={x:e.horizontal.newScroll,y:e.vertical.newScroll}},n.prototype.innerHeight=function(){return this.element==this.element.window?g.viewportHeight():this.adapter.innerHeight()},n.prototype.remove=function(t){delete this.waypoints[t.axis][t.key],this.checkEmpty()},n.prototype.innerWidth=function(){return this.element==this.element.window?g.viewportWidth():this.adapter.innerWidth()},n.prototype.destroy=function(){var t=[];for(var e in this.waypoints)for(var n in this.waypoints[e])t.push(this.waypoints[e][n]);for(var i=0,o=t.length;i<o;i++)t[i].destroy()},n.prototype.refresh=function(){var t,e=this.element==this.element.window,n=e?void 0:this.adapter.offset(),i={};for(var o in this.handleScroll(),t={horizontal:{contextOffset:e?0:n.left,contextScroll:e?0:this.oldScroll.x,contextDimension:this.innerWidth(),oldScroll:this.oldScroll.x,forward:"right",backward:"left",offsetProp:"left"},vertical:{contextOffset:e?0:n.top,contextScroll:e?0:this.oldScroll.y,contextDimension:this.innerHeight(),oldScroll:this.oldScroll.y,forward:"down",backward:"up",offsetProp:"top"}}){var r=t[o];for(var s in this.waypoints[o]){var a,l,u,c,d=this.waypoints[o][s],h=d.options.offset,p=d.triggerPoint,f=0,y=null==p;d.element!==d.element.window&&(f=d.adapter.offset()[r.offsetProp]),"function"==typeof h?h=h.apply(d):"string"==typeof h&&(h=parseFloat(h),-1<d.options.offset.indexOf("%")&&(h=Math.ceil(r.contextDimension*h/100))),a=r.contextScroll-r.contextOffset,d.triggerPoint=Math.floor(f+a-h),l=p<r.oldScroll,u=d.triggerPoint>=r.oldScroll,c=!l&&!u,!y&&(l&&u)?(d.queueTrigger(r.backward),i[d.group.id]=d.group):(!y&&c||y&&r.oldScroll>=d.triggerPoint)&&(d.queueTrigger(r.forward),i[d.group.id]=d.group)}}return g.requestAnimationFrame(function(){for(var t in i)i[t].flushTriggers()}),this},n.findOrCreateByElement=function(t){return n.findByElement(t)||new n(t)},n.refreshAll=function(){for(var t in o)o[t].refresh()},n.findByElement=function(t){return o[t.waypointContextKey]},window.onload=function(){t&&t(),n.refreshAll()},g.requestAnimationFrame=function(t){(window.requestAnimationFrame||window.mozRequestAnimationFrame||window.webkitRequestAnimationFrame||e).call(window,t)},g.Context=n}(),function(){"use strict";function s(t,e){return t.triggerPoint-e.triggerPoint}function a(t,e){return e.triggerPoint-t.triggerPoint}function e(t){this.name=t.name,this.axis=t.axis,this.id=this.name+"-"+this.axis,this.waypoints=[],this.clearTriggerQueues(),n[this.axis][this.name]=this}var n={vertical:{},horizontal:{}},i=window.Waypoint;e.prototype.add=function(t){this.waypoints.push(t)},e.prototype.clearTriggerQueues=function(){this.triggerQueues={up:[],down:[],left:[],right:[]}},e.prototype.flushTriggers=function(){for(var t in this.triggerQueues){var e=this.triggerQueues[t],n="up"===t||"left"===t;e.sort(n?a:s);for(var i=0,o=e.length;i<o;i+=1){var r=e[i];!r.options.continuous&&i!==e.length-1||r.trigger([t])}}this.clearTriggerQueues()},e.prototype.next=function(t){this.waypoints.sort(s);var e=i.Adapter.inArray(t,this.waypoints);return e===this.waypoints.length-1?null:this.waypoints[e+1]},e.prototype.previous=function(t){this.waypoints.sort(s);var e=i.Adapter.inArray(t,this.waypoints);return e?this.waypoints[e-1]:null},e.prototype.queueTrigger=function(t,e){this.triggerQueues[e].push(t)},e.prototype.remove=function(t){var e=i.Adapter.inArray(t,this.waypoints);-1<e&&this.waypoints.splice(e,1)},e.prototype.first=function(){return this.waypoints[0]},e.prototype.last=function(){return this.waypoints[this.waypoints.length-1]},e.findOrCreate=function(t){return n[t.axis][t.name]||new e(t)},i.Group=e}(),function(){"use strict";function n(t){this.$element=i(t)}var i=window.jQuery,t=window.Waypoint;i.each(["innerHeight","innerWidth","off","offset","on","outerHeight","outerWidth","scrollLeft","scrollTop"],function(t,e){n.prototype[e]=function(){var t=Array.prototype.slice.call(arguments);return this.$element[e].apply(this.$element,t)}}),i.each(["extend","inArray","isEmptyObject"],function(t,e){n[e]=i[e]}),t.adapters.push({name:"jquery",Adapter:n}),t.Adapter=n}(),function(){"use strict";function t(i){return function(){var e=[],n=arguments[0];return i.isFunction(arguments[0])&&((n=i.extend({},arguments[1])).handler=arguments[0]),this.each(function(){var t=i.extend({},n,{element:this});"string"==typeof t.context&&(t.context=i(this).closest(t.context)[0]),e.push(new o(t))}),e}}var o=window.Waypoint;window.jQuery&&(window.jQuery.fn.waypoint=t(window.jQuery)),window.Zepto&&(window.Zepto.fn.waypoint=t(window.Zepto))}()},61:function(t,e,n){"use strict";n.d(e,"a",function(){return i});var i=function(o,r,s){var a,l=0;return function(){for(var t=arguments.length,e=new Array(t),n=0;n<t;n++)e[n]=arguments[n];if(s)a&&clearTimeout(a),a=setTimeout(function(){o.apply(void 0,e)},r);else{var i=(new Date).getTime();if(i-l<r)return;l=i,o.apply(void 0,e)}}}},65:function(t,e,n){"use strict";n.d(e,"a",function(){return w});var i=n(2),o=n.n(i),r=n(66),s=n.n(r),a=n(0),l=n.n(a),u=n(1),c=n.n(u),d=n(7),h=n.n(d),p=n(4),f=n.n(p),y=n(8),g=n.n(y),m=n(19);function v(i){return function(){var t,e=f()(i);if(function(){if("undefined"==typeof Reflect||!o.a)return;if(o.a.sham)return;if("function"==typeof Proxy)return 1;try{return Date.prototype.toString.call(o()(Date,[],function(){})),1}catch(t){return}}()){var n=f()(this).constructor;t=o()(e,arguments,n)}else t=e.apply(this,arguments);return h()(this,t)}}var w=function(t){g()(n,t);var e=v(n);function n(){return l()(this,n),e.apply(this,arguments)}return c()(n,[{key:"delay",value:function(t,e,n){var i=2<arguments.length&&void 0!==n?n:0;return this.on(t,function(){var t=arguments;setTimeout(function(){e.apply(self,s()(t))},i)})}},{key:"once",value:function(e,n){var i=this;return this.on(e,function t(){n.apply(i,Array.prototype.slice.call(arguments)),i.off(e,t)})}}]),n}(n.n(m).a)},736:function(t,e,n){"use strict";n.r(e);var i=n(0),o=n.n(i),r=n(1),s=n.n(r),a=(n(92),n(106));new(function(){function e(t){o()(this,e),this.$element=t,this.initEvent()}return s()(e,[{key:"initEvent",value:function(){var e=this;this.$element.on("click",".es-icon-keyboardarrowdown",function(t){return e.onExpandCourse(t)}),this.$element.on("click",".es-icon-keyboardarrowup",function(t){return e.onCollapseCourse(t)})}},{key:"onExpandCourse",value:function(t){var e=$(t.currentTarget),n=e.parents(".course-item"),i=e.parents(".media").siblings(".js-course-detail-content");0<i.length?this._lessonListShow(i):$.get(e.data("lessonUrl"),{visibility:0},function(t){n.append(t),new a.a({wrapTarget:n})});var o=n.siblings().find(".es-icon-keyboardarrowup");this._lessonListShow(o.parents(".media").siblings(".js-course-detail-content"));var r=n.find(".js-all-courses-link"),s=n.siblings().find(".js-all-courses-link");r.length&&r.removeClass("hidden"),this.hideLink(s),o.removeClass("es-icon-keyboardarrowup").addClass("es-icon-keyboardarrowdown"),e.addClass("es-icon-keyboardarrowup").removeClass("es-icon-keyboardarrowdown")}},{key:"onCollapseCourse",value:function(t){var e=$(t.currentTarget),n=e.parents(".course-item").find(".js-all-courses-link");this.hideLink(n),this._lessonListShow(e.parents(".media").siblings(".js-course-detail-content")),e.addClass("es-icon-keyboardarrowdown").removeClass("es-icon-keyboardarrowup")}},{key:"hideLink",value:function(t){t.length&&t.addClass("hidden")}},{key:"_lessonListShow",value:function(t){0<t.length&&(t.animate({visibility:"toggle",opacity:"toggle",easing:"linear"}),t.height())}}]),e}())($(".class-course-list"))},92:function(t,e,n){"use strict";n.d(e,"b",function(){return a}),n.d(e,"a",function(){return i});var a=function(t,e,n){var i=t.find(".js-remove-icon"),o=t.find(".js-remove-text");i.hasClass(e)?(i.removeClass(e).addClass(n),o&&o.text(Translator.trans("收起"))):(i.removeClass(n).addClass(e),o&&o.text(Translator.trans("展开")))},i=function(t,e,n,i){var o=1<arguments.length&&void 0!==e?e:".js-task-chapter",r=2<arguments.length&&void 0!==n?n:"es-icon-remove",s=3<arguments.length&&void 0!==i?i:"es-icon-anonymous-iconfont";$(0<arguments.length&&void 0!==t?t:"body").on("click",o,function(t){var e=$(t.currentTarget);e.nextUntil(o).animate({height:"toggle",opacity:"toggle"},"normal"),a(e,r,s)})}}});