// repo/resources/wikibase.remoteEntity/wikibase.remoteEntity.entityselector.js
( function ( $, mw ) {
	'use strict';

	function isRemoteEntityEnabled() {
		return !!mw.config.get( 'wbFederatedValuesEnabled' );
	}

	function getSuggestionHost( stub ) {
		var concepturi = ( stub && ( stub.concepturi || ( stub.meta && stub.meta.concepturi ) ) ) || null;
		if ( concepturi ) {
			try { return new URL( concepturi ).host || null; } catch ( e ) {}
		}
		return null;
	}

	function decorateEntitySelectorLabelsForRemoteEntity( selectorProto ) {
		var origCreateLabelFromSuggestion = selectorProto._createLabelFromSuggestion;

		selectorProto._createLabelFromSuggestion = function ( entityStub ) {
			var $label = origCreateLabelFromSuggestion.call( this, entityStub );

			if ( !isRemoteEntityEnabled() ) {
				return $label;
			}

			var host = getSuggestionHost( entityStub );
			if ( !host || host === window.location.host ) {
				return $label; // local → no badge
			}

			var $badge = $( '<span>' )
				.addClass( 'wb-entityselector-remote-badge' )
				.text( host );

			$label.prepend( $badge );
			return $label;
		};
	}

	function decorateEntitySelectorValuesForRemoteEntity( selectorProto ) {
		var origCombineResults = selectorProto._combineResults;
		if ( typeof origCombineResults !== 'function' ) {
			return;
		}

		selectorProto._combineResults = function () {
			var args = Array.prototype.slice.call( arguments );
			var results = args[ 1 ];

			if ( isRemoteEntityEnabled() && Array.isArray( results ) ) {
				results = results.map( function ( suggestion ) {
					// Only rewrite IDs for REMOTE suggestions
					var concepturi = suggestion.concepturi || ( suggestion.meta && suggestion.meta.concepturi );
					var host = concepturi ? ( function () { try { return new URL( concepturi ).host; } catch (e) { return null; } } )() : null;

					if ( concepturi && host && host !== window.location.host ) {
						// Use concept URI as canonical id for remote selections.
						suggestion = $.extend( {}, suggestion, { id: concepturi } );
					}
					return suggestion;
				} );

				args[ 1 ] = results;
			}

			return origCombineResults.apply( this, args );
		};
	}

	function initRemoteEntitySelectorDecorators() {
		if ( !$.wikibase || !$.wikibase.entityselector ) {
			return;
		}
		var selectorProto = $.wikibase.entityselector.prototype;
		decorateEntitySelectorLabelsForRemoteEntity( selectorProto );
		decorateEntitySelectorValuesForRemoteEntity( selectorProto );
	}

	mw.loader.using( [ 'jquery.wikibase.entityselector' ] ).done( initRemoteEntitySelectorDecorators );
}( jQuery, mediaWiki ) );
