( function( mw, wb, $ ) {
	'use strict';

	var MODULE = $.wikibase.snakview.variations,
		PARENT = MODULE.Variation;

	/**
	 * `snakview` `Variation` for displaying and creating
	 * `wikibase.datamodel.PropertySomeValueSnak`s. Only displays a message, does not offer any
	 * additional user interface interaction.
	 * @see jQuery.wikibase.snakview
	 * @see wikibase.datamodel.PropertySomeValueSnak
	 * @class jQuery.wikibase.snakview.varaiations.SomeValue
	 * @extends jQuery.wikibase.snakview.variations.Variation
	 * @since 0.4
	 * @license GPL-2.0+
	 * @author Daniel Werner < daniel.werner@wikimedia.de >
	 *
	 * @constructor
	 */
	MODULE.variation( wb.datamodel.PropertySomeValueSnak, PARENT, {
		/**
		 * @inheritdoc
		 */
		draw: function() {
			// display same message in edit and non-edit mode!
			this.$viewPort.empty().text( mw.msg( 'wikibase-snakview-variations-somevalue-label' ) );
			$( this ).trigger( 'afterdraw' );
		}
	} );

}( mediaWiki, wikibase, jQuery ) );
