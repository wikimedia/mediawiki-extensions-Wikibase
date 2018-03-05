( function ( mw, wb, $ ) {
	'use strict';

	var MODULE = $.wikibase.snakview.variations,
		PARENT = MODULE.Variation;

	/**
	 * `snakview` `Variation` for displaying and creating `wikibase.datamodel.PropertyNoValueSnak`s.
	 * Only displays a message, does not offer any additional user interface interaction.
	 * @see jQuery.wikibase.snakview
	 * @see wikibase.datamodel.PropertyNoValueSnak
	 * @class jQuery.wikibase.snakview.variations.NoValue
	 * @extends jQuery.wikibase.snakview.variations.Variation
	 * @license GPL-2.0-or-later
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 *
	 * @constructor
	 */
	MODULE.variation( wb.datamodel.PropertyNoValueSnak, PARENT, {
		/**
		 * @inheritdoc
		 */
		draw: function () {
			// display same message in edit and non-edit mode!
			this.$viewPort.empty().append(
				$( '<span>' ) // this span is not in any template, see MessageSnakFormatter
					.addClass( 'wikibase-snakview-variation-novaluesnak' )
					.text( mw.msg( 'wikibase-snakview-variations-novalue-label' ) )
			);
			$( this ).trigger( 'afterdraw' );
		}
	} );

}( mediaWiki, wikibase, jQuery ) );
