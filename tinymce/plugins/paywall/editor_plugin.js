(function() {
	tinymce.PluginManager.requireLangPack('paywall');
	tinymce.create('tinymce.plugins.PaywallPlugin', {
		init : function(ed, url) {
			ed.addCommand('mcePaywallInsert', function() {
				ed.execCommand('mceInsertContent', 0, insertPaywall('visual', ''));
			});
			ed.addButton('paywall', {
				title : 'paywall.insert_paywall',
				cmd : 'mcePaywallInsert',
				image : url + '/img/grabimo-16x16.png'
			});
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('paywall', n.nodeName == 'IMG');
			});
		},
		createControl : function(n, cm) {
			return null;
		},
		getInfo : function() {
			return {
				longname : 'WP-Paywall',
				author : 'Grabimo',
				authorurl : 'http://grabimo.com',
				infourl : 'http://grabimo.com/',
				version : '1.0'
			};
		}
	});
	tinymce.PluginManager.add('paywall', tinymce.plugins.PaywallPlugin);
})();
