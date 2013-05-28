/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( mw, wb, $ ) {
	'use strict';

	var MODULE = $.wikibase.snakview.variations,
		PARENT = MODULE.Variation;

	/**
	 * Required snakview variation for displaying and creating PropertySomeValue Snaks. Only displays
	 * a message, doesn't offer any additional user interface interaction.
	 *
	 * @constructor
	 * @extends jQuery.wikibase.snakview.variations.Variation
	 * @since 0.4
	 */
	MODULE.variation( wb.PropertySomeValueSnak, PARENT, {
		/**
		 * @see jQuery.wikibase.snakview.variations.Variation.draw
		 */
		draw: function() {
			// display same message in edit and non-edit mode!
			this.$viewPort.empty().text( mw.msg( 'wikibase-snakview-variations-somevalue-label' ) );
		}
	} );

}( mediaWiki, wikibase, jQuery ) );
