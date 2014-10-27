/**
 * Replacing the native MediaWiki search suggestions with Wikibase's entity selector widget.
 *
 * @license GNU GPL v2+
 * @author Jens Ohlig
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, mw ) {
	'use strict';

	$.widget( 'wikibase.entitysearch', $.wikibase.entityselector, {
		/**
		 *@see jQuery.wikibase.entityselector._createMenuItemFromSuggestion
		 */
		_createMenuItemFromSuggestion: function( suggestion ) {
			var $label = this._createLabelFromSuggestion( suggestion ),
				value = suggestion.label || suggestion.id;

			return new $.wikibase.entityselector.Item( $label, value, suggestion );
		},

		/**
		 * @see jQuery.wikibase.entityselector._createMenuItemFromSuggestion
		 */
		_initMenu: function( ooMenu ) {
			$.wikibase.entityselector.prototype._initMenu.apply( this, arguments );

			ooMenu.element.addClass( 'wikibase-entitysearch-list' );

			$( ooMenu )
			.off( 'selected' )
			.on( 'selected.entitysearch', function( event, item ) {
				if(
					event.originalEvent
					&& /^key/.test( event.originalEvent.type )
					&& !( item instanceof $.ui.ooMenu.CustomItem )
				) {
					window.location.href = item.getEntityStub().url;
				}
			} );

			return ooMenu;
		}

	} );

	$( function() {
		var $form = $( '#searchform ' ),
			$input = $( '#searchInput' ),
			$hiddenInput = $( '<input type="hidden" name="search"/>' );

		/**
		 * Updates the suggestion list special item that triggers a full-text search.
		 */
		function updateSuggestionSpecial() {
			var $suggestionsSpecial = $( '.wb-entitysearch-suggestions .suggestions-special' );
			$suggestionsSpecial.find( '.special-query' ).text( $input.val() );
		}

		/**
		 * Removes the native search box suggestion list.
		 *
		 * @param {HTMLElement} input Search box node
		 */
		function removeSuggestionContext( input ) {
			// Native fetch() updates/re-sets the data attribute with the suggestion context.
			$.data( input, 'suggestionsContext' ).config.fetch = function() {};
			$.removeData( input, 'suggestionsContext' );
		}

		var $searchContaining = $( '<div>' )
			.addClass( 'suggestions-special' )
			.append(
				$( '<div>' )
					.addClass( 'special-label' )
					.text( mw.msg( 'searchsuggest-containing' ) ),
				$( '<div>' )
					.addClass( 'special-query' )
			);

		var $searchMenu = $( '<ul/>' ).ooMenu( {
			customItems: [
				new $.ui.ooMenu.CustomItem( $searchContaining, null, function() {
					$form.submit();
				}, 'wb-entitysearch-suggestions' )
			]
		} );

		$input
		.removeAttr( 'name' )
		.after( $hiddenInput );

		$input
		.one( 'focus', function( event ) {
			if ( $.data( this, 'suggestionsContext' ) ) {
				removeSuggestionContext( this );
			} else {
				// Suggestion context might not be initialized when focusing the search box while
				// the page is still rendered.
				var $input = $( this );
				$input.on( 'keypress.entitysearch', function( event ) {
					if ( $.data( this, 'suggestionsContext' ) ) {
						removeSuggestionContext( this );
						$input.off( '.entitysearch' );
					}
				} );
			}
		} )
		.entitysearch( {
			url: mw.config.get( 'wgServer' ) + mw.config.get( 'wgScriptPath' ) + '/api.php',
			menu: $searchMenu.data( 'ooMenu' ),
			position: $.extend(
				{},
				$.wikibase.entityselector.prototype.options.position,
				{ offset: '-1 2' }
			)
		} )
		.on( 'entityselectoropen', function( event ) {
			updateSuggestionSpecial();
		} )
		.on( 'eachchange', function( event, oldVal ) {
			$hiddenInput.val( '' );
			updateSuggestionSpecial();
		} )
		.on( 'entityselectorselected', function( event, entityId ) {
			$hiddenInput.val( entityId );
		} );

		// TODO: Re-evaluate entity selector input (e.g. hitting "Go" after having hit "Search"
		// before. However, this will require triggering the entity selector's API call and waiting
		// for its response.

		$( '#searchGoButton' ).on( 'click keydown', function( event ) {
			if ( !$input.data( 'entityselector' ) ) {
				return;
			}

			// If an entity is selected, redirect to that entity's page.
			if (
				event.type === 'click'
				|| event.keyCode === $.ui.keyCode.ENTER || event.keyCode === $.ui.keyCode.SPACE
			) {
				var entity = $input.data( 'entityselector' ).selectedEntity();
				if ( entity && entity.url ){
					event.preventDefault(); // Prevent default form submit action.
					window.location.href = entity.url;
				}
			}

		} );

		// Default form submit action: Imitate full-text search.
		// Since we are using the entity selector, if an entity is selected, the entity id is stored
		// in a hidden input element (which has ripped the "name" attribute from the original search
		// box). Therefore, the entity id needs to be replaced by the actual search box (entity
		// selector) content.
		$form.on( 'submit', function( event ) {
			$( this ).find( 'input[name="search"]' ).val( $input.val() );
		} );

	} );

}( jQuery, mediaWiki ) );
