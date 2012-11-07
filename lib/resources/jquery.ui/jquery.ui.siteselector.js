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
			this.menu.element.addClass( 'ui-siteselector-list' )
			.on( 'menufocus', function( event, ui ) {
				ui.item.addClass( 'ui-state-hover' );
			} )
			.on( 'menublur', function( event ) {
				$( this ).children().removeClass( 'ui-state-hover' );
			} );

			var self = this;

			// initially highlight the first list item since it would be selected as fallback item
			// when tabbing out of the input box
			this.element.on( this.widgetName + 'open', function( event ) {
				self.menu.activate(
					$.Event( self.widgetName + 'programmatic' ),
					self.menu.element.children().first()
				);
			} );

			// remove highlight on first (fallback) item when selecting another list item
			this.menu.element.on( 'menufocus', function( event, ui ) {
				if ( ui.item !== self.menu.element.children().first() ) {
					self.menu.element.children().first()
						.children( 'a' ).removeClass( 'ui-state-hover' );
				}
			} );

			// reset highlight on first (fallback) item when bluring (mouse leaves list, pressing
			// up/down key to reset the input box contents to what the user typed initially)
			this.menu.element.on( 'menublur', function( event ) {
				self.menu.element.children().first().children( 'a' ).addClass( 'ui-state-hover' );
			} );
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
