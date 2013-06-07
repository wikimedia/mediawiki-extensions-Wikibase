/**
 * Wikibase site selector widget
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.2
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki at snater.com >
 */
( function( $, wb ) {
	'use strict';

	/**
	 * Site selector
	 * Ehances an input box with auto-complete and auto-suggestion functionality for site ids.
	 *
	 * @example $( 'input' ).siteselector( { resultSet: < list of wikibase Site objects > } );
	 * @desc Creates a simple site selector.
	 *
	 * @option resultSet {wb.Site[]} Set of sites that should be available as selectable options.
	 */
	$.widget( 'wikibase.siteselector', $.ui.suggester, {
		/**
		 * (Additional) default options
		 * @see jQuery.Widget.options
		 */
		options: {
			resultSet: null,
			delay: 0 // overwriting jquery.ui.autocomplete default
		},

		/**
		 * @see ui.suggester._create
		 */
		_create: function() {
			if ( this.options.resultSet === null ) {
				throw new Error( 'wikibase.siteselector requires result set to be specified.' );
			}

			// convert wb.Site objects to the object structure required by jquery.ui.autocomplete
			this.setResultSet( this.options.resultSet );

			$.ui.suggester.prototype._create.call( this );

			this.element.addClass( 'wikibase-siteselector-input' );
			this.menu.element.addClass( 'wikibase-siteselector-list' )
			.on( 'menufocus', function( event, ui ) {
				ui.item.addClass( 'ui-state-hover' );
			} )
			.on( 'menublur', function( event ) {
				$( this ).children().removeClass( 'ui-state-hover' );
			} );

			var self = this;

			// When leaving the input box, replace current (incomplete) value with first auto-
			// suggested value.
			this.element.on( 'blur', function( event ) {
				if ( self.getSelectedSiteId() !== null ) {
					// Loop through the complete result set since the auto suggestion widget's
					// narrowed result set is not reliable / too slow; e.g. do not do this:
					// widget.data( 'menu' ).activate( event, widget.children().filter( ':first' ) );
					// self.element.val( self.menu.active.data( 'item.autocomplete' ).value );
					$.each( self.options.resultSet, function( index, element ) {
						if ( element.site.getId() === self.getSelectedSiteId() ) {
							self.element.val( element.value );
						}
					} );
					self._trigger( 'autocomplete' );
				}
			} );

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
					if ( results[i].site.getId() === additionallyFiltered[0].site.getId() ) {
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
			var value = this.element.val(),
				escapedValue = $.ui.autocomplete.escapeRegex( value ),
				regExp = new RegExp(
					'((?:(?!' + escapedValue +').)*?)(' + escapedValue + ')(.*)', 'i'
				),
				regExpCode = new RegExp(
					'((?:(?!\\(' + escapedValue +').)*?\\()(' + escapedValue + ')(\\S*\\))', 'i'
				); // language code hit

			this.menu.element.children().each( function( i ) {
				var $itemLink = $( this ).find( 'a'),
					matches;

				if ( $itemLink.text().toLowerCase().indexOf( '(' + value.toLowerCase() ) !== -1 ) {
					matches = $itemLink.text().match( regExpCode );
				} else {
					matches = $itemLink.text().match( regExp );
				}

				if( matches ) {
					$itemLink
					.text( matches[1] )
					.append( $( '<b/>' ).text( matches[2] ) )
					.append( document.createTextNode( matches[3] ) );
				}
			} );
		},

		/**
		 * Sets/Updates the result set and defines how the result will be displayed in the
		 * suggestion list.
		 *
		 * @param {wb.Site[]} resultSet
		 */
		setResultSet: function( resultSet ) {
			this.options.resultSet = [];
			for ( var i in resultSet ) {
				var site = resultSet[i];
				this.options.resultSet.push( {
					'label': site.getName() + ' (' + site.getLanguageCode() + ')',
					'value': site.getShortName() + ' (' + site.getLanguageCode() + ')',
					'site': site // additional reference to site object for validation
				} );
			}
		},

		/**
		 * Returns the selected site according to this.getSelectedSiteId().
		 *
		 * @return {wikibase.Site}
		 */
		getSelectedSite: function() {
			var siteId = this.getSelectedSiteId();
			return ( siteId === null ) ? null : wb.getSite( siteId );
		},

		/**
		 * Returns the currently specified/selected site id. If no site id is specified exactly,
		 * a "fallback" id - the closest matching (auto-completed) site id - is returned.
		 *
		 * @return {String} site id
		 */
		getSelectedSiteId: function() {
			// trim and lower...
			var value = $.trim( this.element.val() ).toLowerCase(),
				fallbackSearch = false,
				fallback = null,
				suggestions = this.menu.element.children();

			if ( value === '' ) {
				return null; // cannot make a decision based on empty string
			}

			// fallback can only be found when there are suggestions
			if ( suggestions.is( ':visible' ) && suggestions.length > 0 ) {
				fallbackSearch = suggestions.first().text().toLowerCase();
			}

			for ( var i in this.options.resultSet ) {
				// search the site which matches the input string in any way
				var currentItem = this.options.resultSet[i];
				if ( value === currentItem.site.getId().toLowerCase()
					|| value === currentItem.site.getShortName().toLowerCase()
					|| value === currentItem.value.toLowerCase()
					|| value === currentItem.label.toLowerCase()
				) {
					return currentItem.site.getId();
				}
				// check whether this string matches the fallback (if any);
				// ensure that the current input value would actually auto-complete to the fallback
				if ( fallbackSearch && fallbackSearch === currentItem.label.toLowerCase() && (
					currentItem.site.getId().toLowerCase().indexOf( value ) !== -1
					|| currentItem.site.getShortName().toLowerCase().indexOf( value ) !== -1
					|| currentItem.value.toLowerCase().indexOf( value ) !== -1
					|| currentItem.label.toLowerCase().indexOf( value ) !== -1
				) ) {
					fallbackSearch = false; // fallback found, do not search any longer for any
					fallback = currentItem.site.getId();
				}
			}

			return fallback; // not found (invalid) or fallback
		},

		/**
		 * @see ui.suggester.destroy
		 */
		destroy: function() {
			this.element.removeClass( 'wikibase-siteselector-input' );
			$.ui.suggester.prototype.destroy.call( this );
		}

	} );

	$.widget.bridge( 'siteselector', $.wikibase.siteselector );

} )( jQuery, wikibase );
