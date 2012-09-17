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
( function( $ ) {
	'use strict';

	/**
	 * This widget adds a few enhancements to jquery.ui.autocomplete, e.g. adding a scrollbar when a
	 * certain number of items is listed in the suggestion list, highlighting matching characters
	 * in the suggestions and dealing with language direction.
	 * See jquery.ui.autocomplete for further documentation - just listing additional options here.
	 *
	 * @example $( 'input' ).wikibaseAutocomplete( { source: ['a', 'b', 'c'] } );
	 * @desc Creates a simple autocompletion input element passing an array as result set.
	 *
	 * @option Integer maxItems (optional) If the number of suggestions is higher than maxItems, the
	 *         suggestion list will be made scrollable.
	 *         Default value: 10
	 */
	$.widget( 'wb.autocomplete', $.ui.autocomplete, {

		options: {
			maxItems: 10 // maximum number of list items; show scrollbar if exceeded
		},

		_create: function() {
			$.ui.autocomplete.prototype._create.call( this );

			this.element.on( 'autocompleteopen', $.proxy( function( event ) {
				this._updateDirection();
				this._highlightMatchingCharacters();
			}, this ) );

			// since results list does not reposition automatically on resize, just close it
			// (one resize event handler is enough for all widgets)
			$( window ).off( 'wikibase.ui.AutocompleteInterface' );
			$( window ).on( 'resize.wikibase.ui.AutocompleteInterface', $.proxy( function() {
				if ( $( '.ui-autocomplete-input' ).length > 0 ) {
					$( '.ui-autocomplete-input' ).data( 'autocomplete' ).close( {} );
				}
			}, this ) );

		},

		/**
		 * Resizes the menu's height to the height of maximum list items.
		 *
		 * @see jQuery.ui.autocomplete._resizeMenu
		 */
		_resizeMenu: function() {
			$.ui.autocomplete.prototype._resizeMenu.call( this );

			var menu = this.menu.element;
			menu.css( 'minWidth', 'auto' );
			if ( menu.children().length > this.options.maxItems ) {
				var fixedHeight = 0;
				for ( var i = 0; i < this.options.maxItems; i++ ) {
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
			menu.css(
				'minWidth',
				this.element.outerWidth() - ( menu.outerWidth() - menu.width() ) + 'px'
			);
		},

		/**
		 * Makes autocomplete results list strech from the right side of the input box in rtl.
		 */
		_updateDirection: function() {
			if (
				this.element.attr( 'dir' ) === 'rtl' ||
					(
						typeof this.element.attr( 'dir' ) === 'undefined' &&
							document.documentElement.dir === 'rtl'
						)
				) {
				this.options.position.my = 'right top';
				this.options.position.at = 'right bottom';
				this.menu.element.position( $.extend( {
					of: this.element
				}, this.options.position ) );

				// to display rtl and ltr correctly
				// sometimes a rtl wiki can have ltr page names, etc. (try ".gov")
				this.menu.element.children().attr( {
					'dir': 'auto'
				} );
			}
		},

		/**
		 * highlight matching input characters in results
		 */
		_highlightMatchingCharacters: function() {
			var regexp = new RegExp(
				'(' + $.ui.autocomplete.escapeRegex( this.element.val() ) + ')', 'i'
			);
			this.menu.element.children().each( function( i ) {
				$( this ).find( 'a' ).html(
					$( this ).find( 'a' ).text().replace( regexp, '<b>$1</b>' )
				);
			} );
		}

	} );

	$.widget.bridge( 'wikibaseAutocomplete', $.wb.autocomplete );

} )( jQuery );
