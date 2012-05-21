/**
 * JavasSript for 'wikibase' extension
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.utilities.jQuery.ui.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
"use strict";

/**
 * ui related collection of jQuery extensions of the Wikibase extension
 * @var Object
 */
window.wikibase.utilities.jQuery.ui = window.wikibase.utilities.jQuery.ui || {};

/**
 * Gets the width of the OS scrollbar
 *
 *! Copyright (c) 2008 Brandon Aaron (brandon.aaron@gmail.com || http://brandonaaron.net)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 */
( function( $ ) {
	var scrollbarWidth = 0;
	$.getScrollbarWidth = function() {
		if ( !scrollbarWidth ) {
			if ( $.browser.msie ) {
				var $textarea1 = $( '<textarea cols="10" rows="2"></textarea>' )
					.css( { position: 'absolute', top: -1000, left: -1000 } ).appendTo( 'body' ),
					$textarea2 = $( '<textarea cols="10" rows="2" style="overflow: hidden;"></textarea>' )
						.css( { position: 'absolute', top: -1000, left: -1000 } ).appendTo( 'body' );
				scrollbarWidth = $textarea1.width() - $textarea2.width();
				$textarea1.add( $textarea2 ).remove();
			} else {
				var $div = $( '<div />' )
					.css( { width: 100, height: 100, overflow: 'auto', position: 'absolute', top: -1000, left: -1000 } )
					.prependTo( 'body' ).append( '<div />' ).find( 'div' )
					.css( { width: '100%', height: 200 } );
				scrollbarWidth = 100 - $div.width();
				$div.parent().remove();
			}
		}
		return scrollbarWidth;
	};
} )( jQuery );
