( function ( $, mw ) {
	'use strict';

	// Bail out quickly if federation is off or widgets aren't there.
	function isFederationEnabled() {
		return !!mw.config.get( 'wbFederationEnabled' );
	}

	function initFederationEntitySearchUI() {
		if ( !$.wikibase || !$.wikibase.entityselector ) {
			return;
		}

		var selectorProto = $.wikibase.entityselector.prototype;

		// Keep original implementation so we can delegate to it.
		var origCreateLabelFromSuggestion = selectorProto._createLabelFromSuggestion;

		/**
		 * Decorate labels for "remote" results with a small text badge.
		 *
		 * This runs for all users of jQuery.wikibase.entityselector
		 * (including the entitysearch widget which extends it).
		 */
		selectorProto._createLabelFromSuggestion = function ( entityStub ) {
			// Call the original implementation first.
			var $label = origCreateLabelFromSuggestion.call( this, entityStub );

			// Only do anything if federation is enabled and this looks like a remote result.
			if ( !isFederationEnabled() || !entityStub ) {
				return $label;
			}

			// Back-end should set meta.repository to "wikidata" (or other repo name) for remote hits.
			// Depending on the serialization you may see entityStub.repository or entityStub.meta.repository.
			var repository = entityStub.repository ||
				( entityStub.meta && entityStub.meta.repository );

			if ( !repository || repository === 'local' ) {
				return $label;
			}

			// Create a badge span. Message can later be made repo-specific if needed.
			// TODO: Federation - handle indirection here for different sources,
			// possibly deriving from conceptUri. Also, i18n?
			var badgeText = 'Wikibase';

			var $badge = $( '<span>' )
				.addClass( 'wb-entityselector-remote-badge' )
				.text( badgeText );

			// Attach the badge at the end of the suggestion content.
			// The base widget wraps label+description in .ui-entityselector-itemcontent,
			// so appending here keeps things visually grouped.
			// TODO: Federation - prepending because using CSS float:right,
			// switch to flexbox and append as that may be more appropriate...
			$label.prepend( $badge );

			return $label;
		};
	}

	// Run after the core widgets are available.
	mw.loader.using( [ 'jquery.wikibase.entityselector' ] ).done( initFederationEntitySearchUI );

}( jQuery, mediaWiki ) );
