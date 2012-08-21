/**
 * Wikibase extension of jquery.ui.autocomplete
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
'use strict';


( function( $ ) {
	/**
	 * @example $( 'input' ).wikibaseAutocomplete( { source: ['a', 'b', 'c'] });
	 */
	$.fn.wikibaseAutocomplete = function( options ) {
		/**
		 * how many items the dropdown should containg before toggling scrollbar
		 * @const int
		 */
		var MAX_ITEMS = 10;

		this.filter( 'input:text' ).each( function() {
			$( this ).autocomplete( options )
				.on( 'autocompleteopen', $.proxy( function( event ) {
				// resize menu height to height of MAX_ITEMS
				var menu = this.data('autocomplete').menu.element;
				menu.css( 'minWidth', 'auto' );
				if ( menu.children().length > MAX_ITEMS ) {
					var fixedHeight = 0;
					for ( var i = 0; i < MAX_ITEMS ; i++ ) {
						fixedHeight += $( menu.children()[i] ).height();
					}
					menu.width( menu.width() + $.getScrollbarWidth() );
					menu.height( fixedHeight );
					menu.css( 'overflowY', 'scroll' );
				} else {
					menu.width( 'auto' );
					menu.height( 'auto' );
					menu.css( 'overflowY', 'auto' );
				}
				menu.css( 'minWidth', this.data('autocomplete').element.outerWidth() - ( menu.outerWidth() - menu.width() ) + 'px' );
			}, $( this ) ) );
		} );
		return this;
	}
} )( jQuery );
