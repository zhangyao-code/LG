define("arale/autocomplete/1.2.2/textarea-complete-debug", [ "$-debug", "gallery/selection/0.9.0/selection-debug", "./autocomplete-debug", "arale/overlay/1.1.1/overlay-debug", "arale/position/1.0.1/position-debug", "arale/iframe-shim/1.0.2/iframe-shim-debug", "arale/widget/1.1.1/widget-debug", "arale/base/1.1.1/base-debug", "arale/class/1.1.0/class-debug", "arale/events/1.1.0/events-debug", "arale/templatable/0.9.1/templatable-debug", "gallery/handlebars/1.0.2/handlebars-debug", "./data-source-debug", "./filter-debug", "./autocomplete-debug.handlebars" ], function(require, exports, module) {
    var $ = require("$-debug");
    var selection = require("gallery/selection/0.9.0/selection-debug");
    var AutoComplete = require("./autocomplete-debug");
    var TextareaComplete = AutoComplete.extend({
        attrs: {
            cursor: false
        },
        setup: function() {
            TextareaComplete.superclass.setup.call(this);
            this.sel = selection(this.get("trigger"));
            var inputFilter = this.get("inputFilter"), that = this;
            this.set("inputFilter", function(val) {
                var v = val.substring(0, that.sel.cursor()[1]);
                return inputFilter.call(that, v);
            });
            if (this.get("cursor")) {
                this.mirror = Mirror.init(this.get("trigger"));
                this.dataSource.before("getData", function() {
                    that.mirror.setContent(that.get("inputValue"), that.queryValue, that.sel.cursor());
                });
            }
        },
        _keyUp: function(e) {
            if (this.get("visible")) {
                e.preventDefault();
                if (this.get("data").length) {
                    this._step(-1);
                }
            }
        },
        _keyDown: function(e) {
            if (this.get("visible")) {
                e.preventDefault();
                if (this.get("data").length) {
                    this._step(1);
                }
            }
        },
        _keyEnter: function(e) {
            // ?????????????????????????????????????????????
            if (this.get("visible")) {
                if (this.currentItem) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    // ?????????????????????????????? keydown ??????
                    this.selectItem();
                } else {
                    this.hide();
                }
            }
        },
        show: function() {
            var cursor = this.get("cursor");
            if (cursor) {
                if ($.isArray(cursor)) {
                    var offset = cursor;
                } else {
                    var offset = [ 0, 0 ];
                }
                var pos = this.mirror.getFlagRect();
                var align = this.get("align");
                align.baseXY = [ pos.left + offset[0], pos.bottom + offset[1] ];
                align.selfXY = [ 0, 0 ];
                this.set("align", align);
            }
            TextareaComplete.superclass.show.call(this);
        },
        selectItem: function() {
            this.hide();
            var item = this.currentItem, index = this.get("selectedIndex"), data = this.get("data")[index];
            if (item) {
                var matchKey = item.attr("data-value");
                var right = this.sel.cursor()[1];
                var left = right - this.queryValue.length;
                this.sel.cursor(left, right).text("").append(matchKey, "right");
                var value = this.get("trigger").val();
                this.set("inputValue", value);
                this.mirror && this.mirror.setContent(value, "", this.sel.cursor());
                this.trigger("itemSelect", data);
                this._clear();
            }
        }
    });
    // ??????????????????
    // MIT https://github.com/ichord/At.js/blob/master/js/jquery.atwho.js
    var Mirror = {
        mirror: null,
        css: [ "overflowY", "height", "width", "paddingTop", "paddingLeft", "paddingRight", "paddingBottom", "marginTop", "marginLeft", "marginRight", "marginBottom", "fontFamily", "borderStyle", "borderWidth", "wordWrap", "fontSize", "lineHeight", "overflowX" ],
        init: function(origin) {
            origin = $(origin);
            var css = {
                position: "absolute",
                left: -9999,
                top: 0,
                zIndex: -2e4,
                "white-space": "pre-wrap"
            };
            $.each(this.css, function(i, p) {
                return css[p] = origin.css(p);
            });
            this.mirror = $("<div><span></span></div>").css(css).insertAfter(origin);
            return this;
        },
        setContent: function(content, query, cursor) {
            var left = query ? cursor[1] - query.length : cursor[1];
            var right = cursor[1];
            var v = [ "<span>", content.substring(0, left), "</span>", '<span id="flag">', query || "", "</span>", "<span>", content.substring(right), "</span>" ].join("");
            this.mirror.html(v);
            return this;
        },
        getFlagRect: function() {
            var pos, rect, flag;
            flag = this.mirror.find("span#flag");
            pos = flag.position();
            rect = {
                left: pos.left,
                right: flag.width() + pos.left,
                top: pos.top,
                bottom: flag.height() + pos.top
            };
            return rect;
        }
    };
    module.exports = TextareaComplete;
});

define("arale/autocomplete/1.2.2/autocomplete-debug", [ "$-debug", "arale/overlay/1.1.1/overlay-debug", "arale/position/1.0.1/position-debug", "arale/iframe-shim/1.0.2/iframe-shim-debug", "arale/widget/1.1.1/widget-debug", "arale/base/1.1.1/base-debug", "arale/class/1.1.0/class-debug", "arale/events/1.1.0/events-debug", "arale/templatable/0.9.1/templatable-debug", "gallery/handlebars/1.0.2/handlebars-debug", "arale/autocomplete/1.2.2/data-source-debug", "arale/autocomplete/1.2.2/filter-debug" ], function(require, exports, module) {
    var $ = require("$-debug");
    var Overlay = require("arale/overlay/1.1.1/overlay-debug");
    var Templatable = require("arale/templatable/0.9.1/templatable-debug");
    var DataSource = require("arale/autocomplete/1.2.2/data-source-debug");
    var Filter = require("arale/autocomplete/1.2.2/filter-debug");
    var template = require("arale/autocomplete/1.2.2/autocomplete-debug.handlebars");
    var isIE = (window.navigator.userAgent || "").toLowerCase().indexOf("msie") !== -1;
    // keyCode
    var KEY = {
        UP: 38,
        DOWN: 40,
        LEFT: 37,
        RIGHT: 39,
        ENTER: 13,
        ESC: 27,
        BACKSPACE: 8
    };
    var AutoComplete = Overlay.extend({
        Implements: Templatable,
        attrs: {
            // ????????????
            trigger: {
                value: null,
                // required
                getter: function(val) {
                    return $(val);
                }
            },
            classPrefix: "ui-autocomplete",
            align: {
                baseXY: [ 0, "100%" ]
            },
            template: template,
            submitOnEnter: true,
            // ???????????????????????????
            selectItem: true,
            // ????????????????????? selectItem ??????
            dataSource: [],
            //?????????????????? Array, URL, Object, Function
            locator: "data",
            filter: undefined,
            // ????????????
            inputFilter: function(v) {
                return v;
            },
            // ????????????
            disabled: false,
            selectFirst: false,
            delay: 100,
            // ????????????????????????
            selectedIndex: undefined,
            inputValue: null,
            // ?????????????????? value
            data: null
        },
        events: {
            // mousedown ?????? blur ??????????????????????????? blur ????????????
            // see _blurEvent
            "mousedown [data-role=item]": function(e) {
                var i = this.items.index(e.currentTarget);
                this.set("selectedIndex", i);
                if (this.get("selectItem")) {
                    this.selectItem();
                    this._firstMousedown = true;
                }
            },
            mousedown: function() {
                this._secondMousedown = true;
            },
            "click [data-role=item]": function() {
                // ?????? selectItem ??????????????? 
                if (!this.get("selectItem")) {
                    this.hide();
                }
            },
            "mouseenter [data-role=item]": function(e) {
                var className = this.get("classPrefix") + "-item-hover";
                if (this.currentItem) this.currentItem.removeClass(className);
                $(e.currentTarget).addClass(className);
            },
            "mouseleave [data-role=item]": function(e) {
                var className = this.get("classPrefix") + "-item-hover";
                $(e.currentTarget).removeClass(className);
            }
        },
        templateHelpers: {
            // ?????????????????????????????? hl ?????????
            highlightItem: highlightItem
        },
        parseElement: function() {
            this.set("model", {
                classPrefix: this.get("classPrefix"),
                items: []
            });
            AutoComplete.superclass.parseElement.call(this);
        },
        setup: function() {
            var trigger = this.get("trigger"), that = this;
            AutoComplete.superclass.setup.call(this);
            // ??????????????????
            this.dataSource = new DataSource({
                source: this.get("dataSource")
            }).on("data", this._filterData, this);
            this._initFilter();
            // ????????? filter
            this._blurHide([ trigger ]);
            this._tweakAlignDefaultValue();
            trigger.attr("autocomplete", "off");
            this.delegateEvents(trigger, "blur.autocomplete", $.proxy(this._blurEvent, this));
            this.delegateEvents(trigger, "keydown.autocomplete", $.proxy(this._keydownEvent, this));
            this.delegateEvents(trigger, "keyup.autocomplete", function() {
                clearTimeout(that._timeout);
                that._timeout = setTimeout(function() {
                    that._timeout = null;
                    that._keyupEvent.call(that);
                }, that.get("delay"));
            });
        },
        destroy: function() {
            this._clear();
            this.element.remove();
            AutoComplete.superclass.destroy.call(this);
        },
        hide: function() {
            // ????????????????????????????????????
            if (this._timeout) clearTimeout(this._timeout);
            this.dataSource.abort();
            AutoComplete.superclass.hide.call(this);
        },
        // Public Methods
        // --------------
        selectItem: function() {
            this.hide();
            var item = this.currentItem, index = this.get("selectedIndex"), data = this.get("data")[index];
            if (item) {
                var matchKey = item.attr("data-value");
                this.get("trigger").val(matchKey);
                this.set("inputValue", matchKey, {
                    silent: true
                });
                this.trigger("itemSelect", data);
                this._clear();
            }
        },
        setInputValue: function(val) {
            if (this.get("inputValue") !== val) {
                // ??????????????????
                this._start = true;
                this.set("inputValue", val);
                // ??????????????????????????? #44
                var trigger = this.get("trigger");
                if (trigger.val() !== val) {
                    trigger.val(val);
                }
            }
        },
        // Private Methods
        // ---------------
        // 1. ?????????????????????????????????
        _onRenderInputValue: function(val) {
            if (this._start && val) {
                var oldQueryValue = this.queryValue;
                this.queryValue = this.get("inputFilter").call(this, val);
                // ?????? query ??????????????????????????????
                if (this.queryValue && this.queryValue !== oldQueryValue) {
                    this.dataSource.abort();
                    this.dataSource.getData(this.queryValue);
                }
            } else {
                this.queryValue = "";
            }
            if (val === "" || !this.queryValue) {
                this.set("data", []);
                this.hide();
            }
            delete this._start;
        },
        // 2. ??????????????????????????????
        _filterData: function(data) {
            var filter = this.get("filter"), locator = this.get("locator");
            // ??????????????????
            data = locateResult(locator, data);
            // ????????????
            data = filter.func.call(this, data, this.queryValue, filter.options);
            this.set("data", data);
        },
        // 3. ????????????????????????
        _onRenderData: function(data) {
            // ????????????
            this._clear();
            // ????????????
            this.set("model", {
                items: data
            });
            this.renderPartial("[data-role=items]");
            // ????????????????????????
            this.items = this.$("[data-role=items]").children();
            this.currentItem = null;
            if (this.get("selectFirst")) {
                this.set("selectedIndex", 0);
            }
            // data-role=items ??????????????????
            if ($.trim(this.$("[data-role=items]").text())) {
                this.show();
            } else {
                this.hide();
            }
        },
        // ????????????????????????
        _onRenderSelectedIndex: function(index) {
            if (index === -1) return;
            var className = this.get("classPrefix") + "-item-hover";
            if (this.currentItem) {
                this.currentItem.removeClass(className);
            }
            this.currentItem = this.items.eq(index).addClass(className);
            this.trigger("indexChange", index, this.lastIndex);
            this.lastIndex = index;
        },
        _initFilter: function() {
            var filter = this.get("filter");
            // ?????? filter ????????????
            if (filter === undefined) {
                // ?????????????????????????????????????????????
                if (this.dataSource.get("type") === "url") {
                    filter = null;
                } else {
                    filter = {
                        name: "startsWith",
                        func: Filter["startsWith"],
                        options: {
                            key: "value"
                        }
                    };
                }
            } else {
                // object ?????????
                // {
                //   name: '',
                //   options: {}
                // }
                if ($.isPlainObject(filter)) {
                    if (Filter[filter.name]) {
                        filter = {
                            name: filter.name,
                            func: Filter[filter.name],
                            options: filter.options
                        };
                    } else {
                        filter = null;
                    }
                } else if ($.isFunction(filter)) {
                    filter = {
                        func: filter
                    };
                } else {
                    // ?????????????????? FILTER ??????
                    if (Filter[filter]) {
                        filter = {
                            name: filter,
                            func: Filter[filter]
                        };
                    } else {
                        filter = null;
                    }
                }
            }
            // filter ??? null???????????? default
            if (!filter) {
                filter = {
                    name: "default",
                    func: Filter["default"]
                };
            }
            this.set("filter", filter);
        },
        _blurEvent: function() {
            if (isIE) return;
            // https://github.com/aralejs/autocomplete/issues/26
            if (!this._secondMousedown) {
                this.hide();
            } else if (this._firstMousedown) {
                this.get("trigger").focus();
                this.hide();
            }
            delete this._firstMousedown;
            delete this._secondMousedown;
        },
        _keyupEvent: function() {
            if (this.get("disabled")) return;
            if (this._keyupStart) {
                delete this._keyupStart;
                // ??????????????????
                var v = this.get("trigger").val();
                this.setInputValue(v);
            }
        },
        _keydownEvent: function(e) {
            if (this.get("disabled")) return;
            // ???????????????
            delete this._keyupStart;
            switch (e.which) {
              case KEY.ESC:
                this.hide();
                break;

              // top arrow
                case KEY.UP:
                this._keyUp(e);
                break;

              // bottom arrow
                case KEY.DOWN:
                this._keyDown(e);
                break;

              // left arrow
                case KEY.LEFT:
              // right arrow
                case KEY.RIGHT:
                break;

              // enter
                case KEY.ENTER:
                this._keyEnter(e);
                break;

              // default ???????????? keyup
                default:
                this._keyupStart = true;
            }
        },
        _keyUp: function(e) {
            e.preventDefault();
            if (this.get("data").length) {
                if (!this.get("visible")) {
                    this.show();
                    return;
                }
                this._step(-1);
            }
        },
        _keyDown: function(e) {
            e.preventDefault();
            if (this.get("data").length) {
                if (!this.get("visible")) {
                    this.show();
                    return;
                }
                this._step(1);
            }
        },
        _keyEnter: function(e) {
            if (this.get("visible")) {
                this.selectItem();
                // ??????????????????????????????
                if (!this.get("submitOnEnter")) {
                    e.preventDefault();
                }
            }
        },
        // ??????????????????
        _step: function(direction) {
            var currentIndex = this.get("selectedIndex");
            if (direction === -1) {
                // ??????
                if (currentIndex > 0) {
                    this.set("selectedIndex", currentIndex - 1);
                } else {
                    this.set("selectedIndex", this.items.length - 1);
                }
            } else if (direction === 1) {
                // ??????
                if (currentIndex < this.items.length - 1) {
                    this.set("selectedIndex", currentIndex + 1);
                } else {
                    this.set("selectedIndex", 0);
                }
            }
        },
        _clear: function() {
            this.$("[data-role=items]").empty();
            this.set("selectedIndex", -1);
            delete this.items;
            delete this.lastIndex;
            delete this.currentItem;
        },
        // ?????? align ??????????????????
        _tweakAlignDefaultValue: function() {
            var align = this.get("align");
            align.baseElement = this.get("trigger");
            this.set("align", align);
        }
    });
    // ?????????????????????
    AutoComplete._filter = Filter;
    module.exports = AutoComplete;
    function isString(str) {
        return Object.prototype.toString.call(str) === "[object String]";
    }
    // ?????? locator ?????? data ????????????????????????
    // 1. locator ?????? function???????????????????????????
    // 2. locator ?????? string?????????????????????????????????
    //     data {
    //       a: {
    //         b: 'c'
    //       }
    //     }
    //     locator 'a.b'
    // ????????????????????? c
    function locateResult(locator, data) {
        if (!locator) {
            return data;
        }
        if ($.isFunction(locator)) {
            return locator.call(this, data);
        } else if (isString(locator)) {
            var s = locator.split("."), p = data;
            while (s.length) {
                var v = s.shift();
                if (!p[v]) {
                    break;
                }
                p = p[v];
            }
            return p;
        }
        return data;
    }
    function highlightItem(classPrefix, matchKey) {
        var index = this.highlightIndex, cursor = 0, v = matchKey || this.matchKey || "", h = "";
        if ($.isArray(index)) {
            for (var i = 0, l = index.length; i < l; i++) {
                var j = index[i], start, length;
                if ($.isArray(j)) {
                    start = j[0];
                    length = j[1] - j[0];
                } else {
                    start = j;
                    length = 1;
                }
                if (start > cursor) {
                    h += v.substring(cursor, start);
                }
                if (start < v.length) {
                    h += '<span class="' + classPrefix + '-item-hl">' + v.substr(start, length) + "</span>";
                }
                cursor = start + length;
                if (cursor >= v.length) {
                    break;
                }
            }
            if (v.length > cursor) {
                h += v.substring(cursor, v.length);
            }
            return h;
        }
        return v;
    }
});

define("arale/autocomplete/1.2.2/data-source-debug", [ "arale/base/1.1.1/base-debug", "arale/class/1.1.0/class-debug", "arale/events/1.1.0/events-debug", "$-debug" ], function(require, exports, module) {
    var Base = require("arale/base/1.1.1/base-debug");
    var $ = require("$-debug");
    var DataSource = Base.extend({
        attrs: {
            source: null,
            type: "array"
        },
        initialize: function(config) {
            DataSource.superclass.initialize.call(this, config);
            // ???????????????????????? id ????????? callbacks ??????????????????????????????
            // ?????? abort ????????? callbacks???????????????????????????????????????
            this.id = 0;
            this.callbacks = [];
            var source = this.get("source");
            if (isString(source)) {
                this.set("type", "url");
            } else if ($.isArray(source)) {
                this.set("type", "array");
            } else if ($.isPlainObject(source)) {
                this.set("type", "object");
            } else if ($.isFunction(source)) {
                this.set("type", "function");
            } else {
                throw new Error("Source Type Error");
            }
        },
        getData: function(query) {
            return this["_get" + capitalize(this.get("type") || "") + "Data"](query);
        },
        abort: function() {
            this.callbacks = [];
        },
        // ?????????????????????getData => done
        _done: function(data) {
            this.trigger("data", data);
        },
        _getUrlData: function(query) {
            var that = this, options;
            var obj = {
                query: query ? encodeURIComponent(query) : "",
                timestamp: new Date().getTime()
            };
            var url = this.get("source").replace(/{{(.*?)}}/g, function(all, match) {
                return obj[match];
            });
            var callbackId = "callback_" + this.id++;
            this.callbacks.push(callbackId);
            if (/^(https?:\/\/)/.test(url)) {
                options = {
                    dataType: "jsonp"
                };
            } else {
                options = {
                    dataType: "json"
                };
            }
            $.ajax(url, options).success(function(data) {
                if ($.inArray(callbackId, that.callbacks) > -1) {
                    delete that.callbacks[callbackId];
                    that._done(data);
                }
            }).error(function() {
                if ($.inArray(callbackId, that.callbacks) > -1) {
                    delete that.callbacks[callbackId];
                    that._done({});
                }
            });
        },
        _getArrayData: function() {
            var source = this.get("source");
            this._done(source);
            return source;
        },
        _getObjectData: function() {
            var source = this.get("source");
            this._done(source);
            return source;
        },
        _getFunctionData: function(query) {
            var that = this, func = this.get("source");
            // ???????????? false ???????????????
            function done(data) {
                that._done(data);
            }
            var data = func.call(this, query, done);
            if (data) {
                this._done(data);
            }
        }
    });
    module.exports = DataSource;
    function isString(str) {
        return Object.prototype.toString.call(str) === "[object String]";
    }
    function capitalize(str) {
        return str.replace(/^([a-z])/, function(f, m) {
            return m.toUpperCase();
        });
    }
});

define("arale/autocomplete/1.2.2/filter-debug", [ "$-debug" ], function(require, exports, module) {
    var $ = require("$-debug");
    var Filter = {
        "default": function(data, query, options) {
            var result = [];
            $.each(data, function(index, item) {
                var o = {}, matchKey = getMatchKey(item, options);
                if ($.isPlainObject(item)) {
                    o = $.extend({}, item);
                }
                o.matchKey = matchKey;
                result.push(o);
            });
            return result;
        },
        // options: {
        //   key: 'value'
        // }
        startsWith: function(data, query, options) {
            var result = [], l = query.length, reg = new RegExp("^" + escapeKeyword(query));
            if (!l) return [];
            $.each(data, function(index, item) {
                var o = {}, matchKey = getMatchKey(item, options);
                if ($.isPlainObject(item)) {
                    o = $.extend({}, item);
                }
                // ?????? item
                // {
                //   ...   // self property
                //   matchKey: '', // ???????????????
                //   highlightIndex: [] // ???????????????
                // }
                if (reg.test(matchKey)) {
                    o.matchKey = matchKey;
                    if (l > 0) {
                        o.highlightIndex = [ [ 0, l ] ];
                    }
                    result.push(o);
                }
            });
            return result;
        },
        stringMatch: function(data, query, options) {
            query = query || "";
            var result = [], l = query.length;
            if (!l) return [];
            $.each(data, function(index, item) {
                var o = {}, matchKey = getMatchKey(item, options);
                if ($.isPlainObject(item)) {
                    o = $.extend({}, item);
                }
                if (matchKey.indexOf(query) > -1) {
                    o.matchKey = matchKey;
                    o.highlightIndex = stringMatch(matchKey, query);
                    result.push(o);
                }
            });
            return result;
        }
    };
    module.exports = Filter;
    function getMatchKey(item, options) {
        if ($.isPlainObject(item)) {
            // ?????????????????? value ??????
            var key = options && options.key || "value";
            return item[key] || "";
        } else {
            return item;
        }
    }
    function stringMatch(matchKey, query) {
        var r = [], a = matchKey.split("");
        var queryIndex = 0, q = query.split("");
        for (var i = 0, l = a.length; i < l; i++) {
            var v = a[i];
            if (v == q[queryIndex]) {
                if (queryIndex === q.length - 1) {
                    r.push([ i - q.length + 1, i + 1 ]);
                    queryIndex = 0;
                    continue;
                }
                queryIndex++;
            } else {
                queryIndex = 0;
            }
        }
        return r;
    }
    // ?????????????????????
    var keyword = /(\[|\[|\]|\^|\$|\||\(|\)|\{|\}|\+|\*|\?|\\)/g;
    function escapeKeyword(str) {
        return (str || "").replace(keyword, "\\$1");
    }
});

define("arale/autocomplete/1.2.2/autocomplete-debug.handlebars", [ "gallery/handlebars/1.0.2/runtime-debug" ], function(require, exports, module) {
    var Handlebars = require("gallery/handlebars/1.0.2/runtime-debug");
    var template = Handlebars.template;
    module.exports = template(function(Handlebars, depth0, helpers, partials, data) {
        this.compilerInfo = [ 3, ">= 1.0.0-rc.4" ];
        helpers = helpers || {};
        for (var key in Handlebars.helpers) {
            helpers[key] = helpers[key] || Handlebars.helpers[key];
        }
        data = data || {};
        var buffer = "", stack1, functionType = "function", escapeExpression = this.escapeExpression, helperMissing = helpers.helperMissing, self = this;
        function program1(depth0, data, depth1) {
            var buffer = "", stack1, stack2, options;
            buffer += '\n            <li data-role="item" class="' + escapeExpression((stack1 = depth1.classPrefix, 
            typeof stack1 === functionType ? stack1.apply(depth0) : stack1)) + '-item" data-value="';
            if (stack2 = helpers.matchKey) {
                stack2 = stack2.call(depth0, {
                    hash: {},
                    data: data
                });
            } else {
                stack2 = depth0.matchKey;
                stack2 = typeof stack2 === functionType ? stack2.apply(depth0) : stack2;
            }
            buffer += escapeExpression(stack2) + '">';
            options = {
                hash: {},
                data: data
            };
            stack2 = (stack1 = helpers.highlightItem, stack1 ? stack1.call(depth0, depth1.classPrefix, depth0.matchKey, options) : helperMissing.call(depth0, "highlightItem", depth1.classPrefix, depth0.matchKey, options));
            if (stack2 || stack2 === 0) {
                buffer += stack2;
            }
            buffer += "</li>\n        ";
            return buffer;
        }
        buffer += '<div class="';
        if (stack1 = helpers.classPrefix) {
            stack1 = stack1.call(depth0, {
                hash: {},
                data: data
            });
        } else {
            stack1 = depth0.classPrefix;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1) + '">\n    <ul class="';
        if (stack1 = helpers.classPrefix) {
            stack1 = stack1.call(depth0, {
                hash: {},
                data: data
            });
        } else {
            stack1 = depth0.classPrefix;
            stack1 = typeof stack1 === functionType ? stack1.apply(depth0) : stack1;
        }
        buffer += escapeExpression(stack1) + '-ctn" data-role="items">\n        ';
        stack1 = helpers.each.call(depth0, depth0.items, {
            hash: {},
            inverse: self.noop,
            fn: self.programWithDepth(1, program1, data, depth0),
            data: data
        });
        if (stack1 || stack1 === 0) {
            buffer += stack1;
        }
        buffer += "\n    </ul>\n</div>\n";
        return buffer;
    });
});
