if (typeof PAYPAL == 'undefined' || !PAYPAL) {
	var PAYPAL = {};
}
PAYPAL.apps = PAYPAL.apps || {};
(function() {
	var defaultConfig = {
		trigger : null,
		expType : null,
		sole : 'true',
		stage : null,
		port : null
	};
	PAYPAL.apps.DGFlow = function(userConfig) {
		var that = this;
		that.UI = {};
		that.miniWin = {};
		that._init(userConfig);
		return {
			setTrigger : function(el) {
				that._setTrigger(el);
			},
			startFlow : function(url) {
				var win = that._render();
				if (win.location) {
					win.location = url;
				} else {
					win.src = url;
				}
			},
			closeFlow : function() {
				that._destroy();
			},
			isOpen : function() {
				if (that.dgWindow) {
					if (that.dgWindow == 'incontext') {
						return that.isOpen;
					} else {
						if (typeof that.dgWindow == 'object') {
							return (!that.dgWindow.closed);
						} else {
							return false;
						}
					}
				} else {
					return that.isOpen;
				}
			}
		};
	};
	PAYPAL.apps.DGFlow.prototype = {
		name : 'PPDGFrame',
		isOpen : false,
		NoB : true,
		dgWindow : 'incontext',
		RMC : false,
		_init : function(userConfig) {
			if (userConfig) {
				for ( var key in defaultConfig) {
					if (typeof userConfig[key] !== 'undefined') {
						this[key] = userConfig[key];
					} else {
						this[key] = defaultConfig[key];
					}
				}
			}
			this.port = (this.port == null) ? "" : ":" + this.port;
			this.stage = (this.stage == null) ? "www.paypal.com" : "www."
					+ this.stage + ".paypal.com" + this.port;
			if (this.trigger) {
				this._setTrigger(this.trigger);
			}
			this._addCSS();
			if (this.NoB == true && this.sole == 'true') {
				var url = "https://" + this.stage
						+ "/webapps/checkout/nameOnButton.gif";
				this._getImage(url, this._addImage);
			}
		},
		_launchMiniWin : function() {
			if (this.miniWin.state != undefined) {
				if (!this.miniWin.twin.closed) {
					this.miniWin.twin.focus();
					return this.miniWin;
				} else {
					this.miniWin = {};
					return this._openMiniBrowser();
				}
			} else {
				return this._openMiniBrowser();
			}
		},
		_launchMask : function() {
			this._createMask();
			this._centerLightbox();
			this._bindEvents();
		},
		_render : function() {
			var ua = navigator.userAgent, win;
			if (ua.match(/iPhone|iPod|iPad|Android|Blackberry.*WebKit/i)) {
				win = window.open('', this.name);
				this.dgWindow = win;
				return win;
			} else {
				switch (this.expType) {
				case "customized":
					var _text = "Please continue your token recovery in the secure window we opened. If you don't see it, click the button below.";
					var _imgSrc = "https://www.grabimo.com/images/logo/logo45.png";
					this._buildDOMMB(_text, _imgSrc);
					this._launchMask();
					return this._launchMiniWin();
					break;
                case "mini":
                    this._buildDOMMB();
                    this._launchMask();
                case "popup":
                    return this._launchMiniWin();
                    break;                    
                case "instant":
                    if (!this.RMC) {
                        this._buildDOMMB();
                        this._launchMask();
                        return this._launchMiniWin();
                        break;
                    }				
				default:
					this._buildDOM();
					this._launchMask();
					this.isOpen = true;
					return this.UI.iframe;
				}
			}
		},
		_openMiniBrowser : function() {
			var width = 420, height = 560, left, top, win;
			var winOpened = false;
			if (window.outerWidth) {
				left = Math.round((window.outerWidth - width) / 2)
						+ window.screenX;
				top = Math.round((window.outerHeight - height) / 2)
						+ window.screenY;
			} else if (window.screen.width) {
				left = Math.round((window.screen.width - width) / 2);
				top = Math.round((window.screen.height - height) / 2);
			}
			win = window
					.open(
							'about:blank',
							this.name,
							'top='
									+ top
									+ ', left='
									+ left
									+ ', width='
									+ width
									+ ', height='
									+ height
									+ ', location=1, status=1, toolbar=0, menubar=0, resizable=0, scrollbars=1');
			try {
				win.document
						.write("<style>#myDiv{position:absolute; left:36%; top:27%; font-style:italic; font-weight:bold; font-family:arial,Helvetica,sans-serif; font-size:75%; color:#084482; }</style><body><div id=\"myDiv\">LOADING <span id=\"mySpan\"> </span></div></body><script>var sspan = document.getElementById('mySpan');var int = setInterval(function() { if ((sspan.innerHTML += '.').length == 4)  sspan.innerHTML = '';}  , 200);</script>");
			} catch (err) {
			}
			this.miniWin.state = false;
			this.dgWindow = win;
			winOpened = true;
			if (this.expType == "instant" || this.expType == "mini" || this.expType == "customized") {
				var dgObj = this;
				if (winOpened) {
					intVal = setInterval(function() {
						if (win && win.closed) {
							clearInterval(intVal);
							winOpened = false;
							return dgObj._destroy();
						}
					}, 1000);
				}
				addEvent(this.UI.goBtn, 'click', this._launchMiniWin, this);
				addEvent(this.UI.closer, 'click', this._destroy, this);
			}
			this.miniWin.twin = win;
			return win;
		},
		_addCSS : function() {
			var css = '', styleEl = document.createElement('style');
			css += '#' + this.name
					+ ' { z-index:20002; position:absolute; top:0; left:0; }';
			css += '#' + this.name
					+ ' .panel { z-index:20003; position:relative; }';
			css += '#' + this.name
					+ ' .panel iframe { width:385px; height:550px; border:0; }';
			css += '#'
					+ this.name
					+ ' .mask { z-index:20001; position:absolute; top:0; left:0; background-color:#000; opacity:0.2; filter:alpha(opacity=20); }';
			css += '.nameOnButton { display: inline-block; text-align: center; }';
			css += '.nameOnButton img { border:none; }';
			if ((this.expType == "instant" && !this.RMC)
					|| this.expType == "mini" || this.expType == "customized") {
				css += '#' + this.name
						+ ' .panel { font:12px Arial,Helvetica,sans-serif;}';
				css += '#'
						+ this.name
						+ ' .panel .outer { position:relative; border:0;background: url("https://www.paypalobjects.com/en_US/i/scr/scr_dg_sliding_door_bdr_wf.png") no-repeat scroll left top transparent; }';
				css += '#'
						+ this.name
						+ ' .panel .page #goBtn { border:1px solid #d5bd98; border-right-color:#935e0d; border-bottom-color:#935e0d; background:#ffa822 url("https://www.paypalobjects.com/en_US/i/pui/core/btn_bg_sprite.gif") left 17.5% repeat-x; cursor:pointer; font:12px Arial,Helvetica,sans-serif; margin-top:10px;}';
				css += '#' + this.name
						+ ' .panel .page #goBtn span {margin:5px;}';
				css += '#'
						+ this.name
						+ ' .panel .outer .page { background: url("https://www.paypalobjects.com/en_US/i/scr/scr_dg_sliding_door_bdr_wf.png") no-repeat scroll right top transparent; margin-left:  15px; padding: 10px 10px 0 0; position:relative; min-height: 290px; left:17px; }';
				css += '#'
						+ this.name
						+ ' .panel .outer .page .launcher { padding:0 0 20px 0; width:315px!important; }';
				css += '#'
						+ this.name
						+ ' .panel .outer .page .launcher .logoPara { text-align:right;}';
				css += '#'
						+ this.name
						+ ' .panel .outer .page .launcher .continueText { padding-right:10px;}';
				css += '#'
						+ this.name
						+ ' .panel .minifooter {background: url("https://www.paypalobjects.com/en_US/i/scr/scr_dg_sliding_door_bdr_wf.png") no-repeat scroll right bottom transparent; position: absolute;  width:80%; height:12px; right:0px}';
				css += '#'
						+ this.name
						+ ' .panel .outer .minifootercap {background: url("https://www.paypalobjects.com/en_US/i/scr/scr_dg_sliding_door_bdr_wf.png") no-repeat scroll left bottom transparent; left: -32px; position: absolute; height: 12px; width:80%;}';
				css += '#'
						+ this.name
						+ ' .panel #closer{ top:-10px; right:0; position:absolute;}';
				css += '#'
						+ this.name
						+ ' .panel #closer a{margin:0; padding:0; list-style:none;  text-decoration:none; position:absolute; height:26px; display:block; width:26px; background:url("https://www.paypal.com/en_US/i/btn/btn_dg_close_sprite.png");cursor:pointer;}';
				css += '#'
						+ this.name
						+ ' .panel #closer a:hover{background: url("https://www.paypal.com/en_US/i/btn/btn_dg_close_sprite.png") -26px 0;}';
			}
			styleEl.type = 'text/css';
			styleEl.id = 'dgCSS';
			if (styleEl.styleSheet) {
				styleEl.styleSheet.cssText = css;
			} else {
				styleEl.appendChild(document.createTextNode(css));
			}
			document.getElementsByTagName('head')[0].appendChild(styleEl);
		},
		_buildDOM : function() {
			this.UI.wrapper = document.createElement('div');
			this.UI.wrapper.id = this.name;
			this.UI.panel = document.createElement('div');
			this.UI.panel.className = 'panel';
			try {
				this.UI.iframe = document.createElement('<iframe name="'
						+ this.name + '">');
			} catch (e) {
				this.UI.iframe = document.createElement('iframe');
				this.UI.iframe.name = this.name;
			}
			this.UI.iframe.frameBorder = '0';
			this.UI.iframe.border = '0';
			this.UI.iframe.scrolling = 'no';
			this.UI.iframe.allowTransparency = 'true';
			this.UI.mask = document.createElement('div');
			this.UI.mask.className = 'mask';
			this.UI.panel.appendChild(this.UI.iframe);
			this.UI.wrapper.appendChild(this.UI.mask);
			this.UI.wrapper.appendChild(this.UI.panel);
			document.body.appendChild(this.UI.wrapper);
		},
		_buildDOMMB : function(text, imgSrc) {
			this.UI.wrapper = document.createElement('div');
			this.UI.wrapper.id = this.name;
			this.UI.panel = document.createElement('div');
			this.UI.panel.className = 'panel';
			this.UI.mask = document.createElement('div');
			this.UI.mask.className = 'mask';
			this.UI.outer = document.createElement('div');
			this.UI.outer.className = 'outer';
			this.UI.page = document.createElement('div');
			this.UI.page.className = 'page';
			this.UI.closer = document.createElement('div');
			this.UI.closer.id = 'closer';
			this.UI.closerImg = document.createElement('a');
			this.UI.closerImg.src = '#';
			this.UI.launcher = document.createElement('div');
			this.UI.launcher.className = 'launcher';
			this.UI.loadingPara = document.createElement('p');
			this.UI.loadingPara.className = 'continueText';
			if (text) {
				this.UI.loadingText = document.createTextNode(text);
			} else {
				this.UI.loadingText = document
						.createTextNode("Please continue your purchase in the secure window we opened. If you don't see it, click the button below.");
			}
			this.UI.goBtn = document.createElement('button');
			this.UI.goBtn.id = 'goBtn';
			this.UI.goBtn.value = 'Go';
			this.UI.goBtn.innerHTML = '<span>Go</span>';
			this.UI.goBtn.className = 'button primary';
			this.UI.logoPara = document.createElement('p');
			this.UI.logoPara.className = 'logoPara';
			this.UI.logoImg = document.createElement('img');
			this.UI.logoImg.alt = 'logo';
			if (imgSrc) {
				this.UI.logoImg.src = imgSrc;
			} else {
				this.UI.logoImg.src = 'https://www.paypal.com/en_US/i/logo/logo_paypal_140wx50h.gif';
			}
			this.UI.logoImg.className = 'logo';
			this.UI.minifooter = document.createElement('div');
			this.UI.minifooter.className = 'minifooter';
			this.UI.minifooter.id = 'minifooter';
			this.UI.minifootercap = document.createElement('div');
			this.UI.minifootercap.className = 'minifootercap';
			this.UI.minifootercap.id = 'minifootercap';
			this.UI.wrapper.appendChild(this.UI.mask);
			this.UI.wrapper.appendChild(this.UI.panel);
			document.body.appendChild(this.UI.wrapper);
			this.UI.panel.appendChild(this.UI.outer);
			this.UI.outer.appendChild(this.UI.page);
			this.UI.page.appendChild(this.UI.launcher);
			this.UI.outer.appendChild(this.UI.closer);
			this.UI.closer.appendChild(this.UI.closerImg);
			this.UI.launcher.appendChild(this.UI.logoPara);
			this.UI.logoPara.appendChild(this.UI.logoImg);
			this.UI.loadingPara.appendChild(this.UI.loadingText);
			this.UI.launcher.appendChild(this.UI.loadingPara);
			this.UI.launcher.appendChild(this.UI.goBtn);
			this.UI.page.appendChild(this.UI.minifooter);
			this.UI.page.appendChild(this.UI.minifootercap);
		},
		_createMask : function(e) {
			var windowWidth, windowHeight, scrollWidth, scrollHeight, width, height;
			var actualWidth = (document.documentElement) ? document.documentElement.clientWidth
					: window.innerWidth;
			if (window.innerHeight && window.scrollMaxY) {
				scrollWidth = actualWidth + window.scrollMaxX;
				scrollHeight = window.innerHeight + window.scrollMaxY;
			} else if (document.body.scrollHeight > document.body.offsetHeight) {
				scrollWidth = document.body.scrollWidth;
				scrollHeight = document.body.scrollHeight;
			} else {
				scrollWidth = document.body.offsetWidth;
				scrollHeight = document.body.offsetHeight;
			}
			if (window.innerHeight) {
				windowWidth = actualWidth;
				windowHeight = window.innerHeight;
			} else if (document.documentElement
					&& document.documentElement.clientHeight) {
				windowWidth = document.documentElement.clientWidth;
				windowHeight = document.documentElement.clientHeight;
			} else if (document.body) {
				windowWidth = document.body.clientWidth;
				windowHeight = document.body.clientHeight;
			}
			width = (windowWidth > scrollWidth) ? windowWidth : scrollWidth;
			height = (windowHeight > scrollHeight) ? windowHeight
					: scrollHeight;
			this.UI.mask.style.width = width + 'px';
			this.UI.mask.style.height = height + 'px';
		},
		_centerLightbox : function(e) {
			var width, height, scrollY;
			if (window.innerWidth) {
				width = window.innerWidth;
				height = window.innerHeight;
				scrollY = window.pageYOffset;
			} else if (document.documentElement
					&& (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
				width = document.documentElement.clientWidth;
				height = document.documentElement.clientHeight;
				scrollY = document.documentElement.scrollTop;
			} else if (document.body
					&& (document.body.clientWidth || document.body.clientHeight)) {
				width = document.body.clientWidth;
				height = document.body.clientHeight;
				scrollY = document.body.scrollTop;
			}
			if ((this.expType == "instant" && !this.RMC)
					|| this.expType == "mini" || this.expType == "customized") {
				var panelWidth = 355, panelHeight = 300;
				this.UI.launcher.style.width = panelWidth + "px";
				this.UI.launcher.style.height = panelHeight + "px";
				this.UI.panel.style.left = Math.round((width - panelWidth) / 2)
						+ 'px';
				var panelTop = Math.round((height - 550) / 2) + scrollY + 20;
			} else {
				this.UI.panel.style.left = Math
						.round((width - this.UI.iframe.offsetWidth) / 2)
						+ 'px';
				var panelTop = Math
						.round((height - this.UI.iframe.offsetHeight) / 2)
						+ scrollY;
			}
			if (panelTop < 5) {
				panelTop = 10;
			}
			this.UI.panel.style.top = panelTop + 'px';
		},
		_bindEvents : function() {
			addEvent(window, 'resize', this._createMask, this);
			addEvent(window, 'resize', this._centerLightbox, this);
			addEvent(window, 'unload', this._destroy, this);
		},
		_setTrigger : function(el) {
			if (el.constructor.toString().indexOf('Array') > -1) {
				for (var i = 0; i < el.length; i++) {
					this._setTrigger(el[i]);
				}
			} else {
				el = (typeof el == 'string') ? document.getElementById(el) : el;
				if (el && el.form) {
					el.form.target = this.name;
				} else if (el && el.tagName.toLowerCase() == 'a') {
					el.target = this.name;
				}
				addEvent(el, 'click', this._triggerClickEvent, this);
			}
		},
		_getImage : function(url, callback) {
			if (typeof this.callback != 'undefined') {
				url = this.url;
				callback = this.callback;
			}
			var self = this;
			var imgElement = new Image();
			imgElement.src = "";
			if (imgElement.readyState) {
				imgElement.onreadystatechange = function() {
					if (imgElement.readyState == 'complete'
							|| imgElement.readyState == 'loaded') {
						callback(imgElement, self);
					}
				};
			} else {
				imgElement.onload = function() {
					callback(imgElement, self);
				};
			}
			imgElement.src = url;
		},
		_addImage : function(img, obj) {
			if (checkEmptyImage(img)) {
				obj.RMC = true;
				var url = "https://" + obj.stage
						+ "/webapps/checkout/clearNob.gif";
				var wrapperObj = {};
				wrapperObj.callback = obj._removeImage;
				wrapperObj.url = url;
				wrapperObj.outer = obj;
				var el = obj.trigger;
				if (el != null) {
					if (el.constructor.toString().indexOf('Array') > -1) {
						for (var i = 0; i < el.length; i++) {
							var tempImg = img.cloneNode(true);
							obj._placeImage(el[i], tempImg, wrapperObj);
						}
					} else {
						obj._placeImage(el, img, wrapperObj);
					}
				}
			}
		},
		_placeImage : function(el, img, obj) {
			el = (typeof el == 'string') ? document.getElementById(el) : el;
			var root = getParent(el);
			var spanElement = document.createElement("span");
			spanElement.className = "nameOnButton";
			var lineBreak = document.createElement("br");
			var link = document.createElement("a");
			link.href = "javascript:";
			link.appendChild(img);
			root.insertBefore(spanElement, el);
			spanElement.appendChild(el);
			spanElement.insertBefore(link, el);
			spanElement.insertBefore(lineBreak, el);
			obj.span = spanElement;
			obj.link = link;
			obj.lbreak = lineBreak;
			addEvent(link, 'click', obj.outer._getImage, obj);
		},
		_removeImage : function(img, obj) {
			if (!checkEmptyImage(img)) {
				var el = obj.outer.trigger;
				if (el.constructor.toString().indexOf('Array') > -1) {
					obj.outer._removeMultiImages(obj.outer.trigger);
				} else {
					spanElement = obj.span;
					link = obj.link;
					lineBreak = obj.lbreak;
					spanElement.removeChild(link);
					spanElement.removeChild(lineBreak);
				}
			}
		},
		_removeMultiImages : function(obj) {
			for (var i = 0; i < obj.length; i++) {
				obj[i] = (typeof obj[i] == 'string') ? document
						.getElementById(obj[i]) : obj[i];
				rootNode = getParent(obj[i]);
				if (rootNode.className == 'nameOnButton') {
					lineBreak = getPreviousSibling(obj[i]);
					linkNode = getPreviousSibling(lineBreak);
					rootNode.removeChild(linkNode);
					rootNode.removeChild(lineBreak);
				}
			}
		},
		_triggerClickEvent : function(e) {
			this._render();
		},
		_destroy : function(e) {
			if (typeof this.dgWindow == 'object') {
				try {
					this.dgWindow.close();
				} catch (er) {
				}
			}
			if (document.getElementById('PPDGFrame')) {
				var parentDiv = document.getElementById('PPDGFrame').parentNode;
				parentDiv.removeChild(document.getElementById('PPDGFrame'));
			}
			if (this.isOpen && this.UI.wrapper.parentNode) {
				this.UI.wrapper.parentNode.removeChild(this.UI.wrapper);
			}
			if (this.interval) {
				clearInterval(this.interval);
			}
			removeEvent(window, 'resize', this._createMask);
			removeEvent(window, 'resize', this._centerLightbox);
			removeEvent(window, 'unload', this._destroy);
			removeEvent(window, 'message', this._windowMessageEvent);
			this.isOpen = false;
		}
	};
	var eventCache = [];

	function addEvent(obj, type, fn, scope) {
		scope = scope || obj;
		var wrappedFn;
		if (obj) {
			if (obj.addEventListener) {
				wrappedFn = function(e) {
					fn.call(scope, e);
				};
				obj.addEventListener(type, wrappedFn, false);
			} else if (obj.attachEvent) {
				wrappedFn = function() {
					var e = window.event;
					e.target = e.target || e.srcElement;
					e.preventDefault = function() {
						window.event.returnValue = false;
					};
					fn.call(scope, e);
				};
				obj.attachEvent('on' + type, wrappedFn);
			}
		}
		eventCache.push([ obj, type, fn, wrappedFn ]);
	}

	function removeEvent(obj, type, fn) {
		var wrappedFn, item, len, i;
		for (i = 0; i < eventCache.length; i++) {
			item = eventCache[i];
			if (item[0] == obj && item[1] == type && item[2] == fn) {
				wrappedFn = item[3];
				if (wrappedFn) {
					if (obj.removeEventListener) {
						obj.removeEventListener(type, wrappedFn, false);
					} else if (obj.detachEvent) {
						obj.detachEvent('on' + type, wrappedFn);
					}
				}
			}
		}
	}

	function getParent(el) {
		do {
			el = el.parentNode;
		} while (el && el.nodeType != 1);
		return el;
	}

	function getPreviousSibling(el) {
		do {
			el = el.previousSibling;
		} while (el && el.nodeType != 1);
		return el;
	}

	function checkEmptyImage(img) {
		return (img.width > 1 || img.height > 1);
	}
}());