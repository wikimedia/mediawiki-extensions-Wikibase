/**
 * Replacing the native MediaWiki search suggestions with Wikibase's entity selector widget.
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @license GNU GPL v2+
 * @author Jens Ohlig
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, mw ) {
	'use strict';

	$( document ).ready( function() {
		var $form = $( '#searchform ' ),
			$input = $( '#searchInput' );

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
		 * @param {Object} input Search box node
		 */
		function removeSuggestionContext( input ) {
			// Native fetch() updates/re-sets the data attribute with the suggestion context.
			$.data( input, 'suggestionsContext' ).config.fetch = function() {};
			$.removeData( input, 'suggestionsContext' );
		}

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
		.entityselector( {
			url: mw.config.get( 'wgServer' ) + mw.config.get( 'wgScriptPath' ) + '/api.php',
			emulateSearchBox: true,
			customListItem: {
				content: $( '<div/>' ).addClass( 'suggestions-special' )
					.append( $( '<div/>' ).addClass( 'special-label ' ).text(
						mw.msg( 'searchsuggest-containing' ) )
					)
					.append( $( '<div/>' ).addClass( 'special-query' )
				),
				action: function( event, entityselector ) {
					$form.submit();
				},
				cssClass: 'wb-entitysearch-suggestions'
			}
		} )
		.on( 'entityselectoropen', function( event ) {
			updateSuggestionSpecial();
		} )
		.eachchange( function( event, oldVal ) {
			updateSuggestionSpecial();
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
