/*!
 * word-count.js with Asian Language Support
 *
 * Based on wp-admin/js/word-count.dev.js @ r17936
 */

(function($) {
	wpWordCount = {

		settingsWestern : {
			strip : /<[a-zA-Z\/][^<>]*>/g, // strip HTML tags
			clean : /[0-9.(),;:!?%#$¿'"_+=\\/-]+/g, // regexp to remove punctuation, etc.
			count : /\S\s+/g // counting regexp
		},

		settingsAsian : {
			clean : /[，！？；：（）【】［］。「」﹁﹂“”、·《》…—～〈〉『』〔〕〖〗〘〙〚〛〟]+/g, // listed according to http://en.wikipedia.org/wiki/Chinese_punctuation and http://www.unicode.org/charts/PDF/U3000.pdf
			count : /[^\u0000-\u007F]/g // count all non-ASCII characters
		},

		block : 0,

		wc : function(tx) {
			var t = this, w = $('.word-count'), tc = 0;

			if ( t.block )
				return;

			t.block = 1;

			setTimeout( function() {
				if ( tx ) {
					// remove generally useless stuff first
					tx = tx.replace( t.settingsWestern.strip, ' ' ).replace( /&nbsp;|&#160;/gi, ' ' );

					// count asian characters
					tx = tx.replace( t.settingsAsian.clean, '' );
					tx = tx.replace( t.settingsAsian.count, function(){ tc++; return ''; } );

					// count remaining western characters
					tx = tx.replace( t.settingsWestern.clean, '' );
					tx.replace( t.settingsWestern.count, function(){tc++;} );
				}
				w.html(tc.toString());

				setTimeout( function() { t.block = 0; }, 2000 );
			}, 1 );
		}
	}

	$(document).bind( 'wpcountwords', function(e, txt) {
		wpWordCount.wc(txt);
	});
}(jQuery));
