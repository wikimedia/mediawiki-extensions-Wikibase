// repo/resources/wikibase.federation/wikibase.federation.entityselector.js

( function ( $, mw ) {
	'use strict';

	/**
	 * Simple feature flag check for federation UI behavior.
	 */
	function isFederationUiEnabled() {
		return !!mw.config.get( 'wbFederationEnabled' );
	}

	/**
	 * Extract the repository name from a suggestion / entity stub.
	 *
	 * Back-end may set:
	 *  - stub.repository, or
	 *  - stub.meta.repository
	 *
	 * Returns null for local or missing repository info.
	 *
	 * @param {Object} stub
	 * @return {string|null}
	 */
	function getSuggestionRepository( stub ) {
		if ( !stub ) {
			return null;
		}

		var repository = stub.repository ||
			( stub.meta && stub.meta.repository );

		if ( !repository || repository === 'local' ) {
			return null;
		}

		return repository;
	}

	/**
	 * Decorate suggestion labels in the entityselector to show a small
	 * "remote" badge for federated entities.
	 *
	 * @param {Object} selectorProto $.wikibase.entityselector.prototype
	 */
	function decorateEntitySelectorLabelsForFederation( selectorProto ) {
		var origCreateLabelFromSuggestion = selectorProto._createLabelFromSuggestion;

		selectorProto._createLabelFromSuggestion = function ( entityStub ) {
			var $label = origCreateLabelFromSuggestion.call( this, entityStub );

			if ( !isFederationUiEnabled() ) {
				return $label;
			}

			var repository = getSuggestionRepository( entityStub );
			if ( !repository ) {
				return $label;
			}

			var $badge = $( '<span>' )
				.addClass( 'wb-entityselector-remote-badge' )
				.text( repository );

			// Prepend so it appears left-most; CSS can adjust final placement.
			$label.prepend( $badge );

			return $label;
		};
	}

	/**
	 * Decorate the entityselector so that *remote* suggestions get an id
	 * like "wikidata:Q42" instead of plain "Q42" as early as possible in the
	 * suggestion pipeline.
	 *
	 * This is the critical glue that lets the PHP-side RemoteEntityIdParser
	 * see a namespaced id and return a RemoteEntityId.
	 *
	 * @param {Object} selectorProto $.wikibase.entityselector.prototype
	 */
	function decorateEntitySelectorValuesForFederation( selectorProto ) {
		var origCombineResults = selectorProto._combineResults;

		if ( typeof origCombineResults !== 'function' ) {
			return;
		}

		selectorProto._combineResults = function () {
			var args = Array.prototype.slice.call( arguments );
			var results = args[ 1 ];

			if ( isFederationUiEnabled() && Array.isArray( results ) ) {
				results = results.map( function ( suggestion ) {

					var repository = getSuggestionRepository( suggestion );

					// Only prefix ids for remote suggestions that still look like "Q42".
					if (
						repository &&
						suggestion &&
						typeof suggestion.id === 'string' &&
						suggestion.id.indexOf( ':' ) === -1
					) {
						// Shallow-clone so we don't mutate the original object in place.
						suggestion = $.extend( {}, suggestion, {
							id: repository + ':' + suggestion.id
						} );
					}

					return suggestion;
				} );

				args[ 1 ] = results;
			}

			return origCombineResults.apply( this, args );
		};
	}

	/**
	 * Entry point: apply all federation-related decorations to the
	 * jQuery.wikibase.entityselector widget.
	 */
	function initFederatedEntitySelectorDecorators() {
		if ( !$.wikibase || !$.wikibase.entityselector ) {
			return;
		}

		var selectorProto = $.wikibase.entityselector.prototype;

		decorateEntitySelectorLabelsForFederation( selectorProto );
		decorateEntitySelectorValuesForFederation( selectorProto );
	}

	// Run after the core entityselector widget is available.
	mw.loader.using( [ 'jquery.wikibase.entityselector' ] ).done( initFederatedEntitySelectorDecorators );

}( jQuery, mediaWiki ) );
