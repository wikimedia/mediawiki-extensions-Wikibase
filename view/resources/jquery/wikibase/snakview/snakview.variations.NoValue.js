( function () {
	'use strict';

	var MODULE = require( './snakview.variations.js' ),
		Variation = require( './snakview.variations.Variation.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * `snakview` `Variation` for displaying and creating `datamodel.PropertyNoValueSnak`s.
	 * Only displays a message, does not offer any additional user interface interaction.
	 *
	 * @see jQuery.wikibase.snakview
	 * @see datamodel.PropertyNoValueSnak
	 * @class jQuery.wikibase.snakview.variations.NoValue
	 * @extends jQuery.wikibase.snakview.variations.Variation
	 * @license GPL-2.0-or-later
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 */
	class NoValue extends Variation {
		/**
		 * @inheritDoc
		 */
		setupVariation() {
			this.variationSnakConstructor = datamodel.PropertyNoValueSnak;
			this.variationBaseClass = 'wikibase-snakview-variation-novaluesnak';
		}

		/**
		 * @inheritdoc
		 */
		draw() {
			// display same message in edit and non-edit mode!
			this.$viewPort.empty().append(
				$( '<span>' ) // this span is not in any template, see MessageSnakFormatter
					.addClass( 'wikibase-snakview-variation-novaluesnak' )
					.text( mw.msg( 'wikibase-snakview-variations-novalue-label' ) )
			);
			$( this ).trigger( 'afterdraw' );
		}
	}

	MODULE.registerVariation( 'novalue', NoValue );

}() );
