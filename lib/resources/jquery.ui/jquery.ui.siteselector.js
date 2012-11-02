/**
 * Wikibase site selector widget
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.2
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki at snater.com >
 */
( function( $, undefined ) {
	'use strict';

	/**
	 * Site selector
	 * Ehances an input box with auto-complete and auto-suggestion functionality for site ids.
	 *
	 * @example $( 'input' ).siteselector( { resultSet: < list of wikibase Site objects > } );
	 * @desc Creates a simple site selector.
	 *
	 * @option resultSet {Array} List of wb.Site objects
	 */
	$.widget( 'ui.siteselector', $.ui.suggester, {

		/**
		 * Additional options
		 * @type {Object}
		 */
		options: {
			resultSet: null
		},

		/**
		 * @see ui.suggester._create
		 */
		_create: function() {
			if ( this.options.resultSet === null ) {
				throw new Error( 'ui.siteselector requires result set to be specified.' );
			}

			$.ui.suggester.prototype._create.call( this );

			this.element.addClass( 'ui-siteselector-input' );
			this.menu.element.addClass( 'ui-siteselector-list' );

			var self = this;

			// The following lines remove the highlight from the suggester's first menu item (which
			// is the fallback item that automatically fills the input box when the keyboard's tab
			// button is hit as long as the mouse cursor does not hover another item) when the menu
			// is hovered with the mouse cursor.
			this.menu.element.on(
				'mouseover',
				'li',
				function( event ) {
					// do not remove highlight when the first item is hovered with the mouse cursor
					if ( event.target !== self.menu.element.children().first().children('a')[0] ) {
						self.menu.element.children().first().children( 'a' ).removeClass( 'ui-state-hover' );
					}
				}
			);
			// re-highlight first (fallback) item when moving the mouse off the menu
			self.menu.element.on(
				'mouseout',
				function( event ) {
					self.menu.element.children().first().children( 'a' ).addClass( 'ui-state-hover' );
				}
			);
		},

		/**
		 * @see ui.suggester._request
		 */
		_request: function( request, suggest ) {
			// just matching from the beginning (autocomplete would match anywhere within the string)
			var results = $.grep( this.options.resultSet, function( result, i ) {
				return (
					result.label.toLowerCase().indexOf( request.term.toLowerCase() ) === 0
						|| result.site.getId().indexOf( request.term.toLowerCase() ) === 0
					);
			} );
			// if some site id is specified exactly, move that site to the top for it will
			// be the one picked when leaving the input field
			var additionallyFiltered = $.grep( results, function( result, i ) {
				return ( request.term === result.site.getId() );
			} );
			if ( additionallyFiltered.length > 0 ) { // remove site from original result set
				for ( var i in results ) {
					if ( results[i].site.getId() == additionallyFiltered[0].site.getId() ) {
						results.splice( i, 1 );
						break;
					}
				}
			}
			// put site with exactly hit site id to beginning of complete result set
			$.merge( additionallyFiltered, results );
			suggest( additionallyFiltered );
		},

		/**
		 * Highlights matching characters in the result list.
		 * @see ui.suggester._highlightMatchingCharacters
		 */
		_highlightMatchingCharacters: function() {
			var regExp = new RegExp( '^(' + $.ui.autocomplete.escapeRegex( this.element.val() ) + ')', 'i' );
			var regExpCode = new RegExp(
				'\\((' + $.ui.autocomplete.escapeRegex( this.element.val() ) + ')(\\S*)\\)',
				'i'
			); // check for direct language code hit
			this.menu.element.children().each( function( i ) {
				var node = $( this ).find( 'a' );
				if ( regExpCode.test( node.text() ) ) {
					node.html( node.text().replace( regExpCode, '(<b>$1</b>$2)' ) );
				} else {
					node.html( node.text().replace( regExp, '<b>$1</b>' ) );
				}
			} );
		},

		/**
		 * @see ui.suggester.destroy
		 */
		destroy: function() {
			this.element.removeClass( 'ui-siteselector-input' );
			$.ui.suggester.prototype.destroy.call( this );
		}

	} );

} )( jQuery );
