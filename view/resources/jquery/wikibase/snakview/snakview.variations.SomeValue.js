( function () {
	'use strict';

	var MODULE = require( './snakview.variations.js' ),
		Variation = require( './snakview.variations.Variation.js' ),
		datamodel = require( 'wikibase.datamodel' );

	/**
	 * `snakview` `Variation` for displaying and creating
	 * `datamodel.PropertySomeValueSnak`s. Only displays a message, does not offer any
	 * additional user interface interaction.
	 *
	 * @see jQuery.wikibase.snakview
	 * @see datamodel.PropertySomeValueSnak
	 * @class jQuery.wikibase.snakview.variations.SomeValue
	 * @extends jQuery.wikibase.snakview.variations.Variation
	 * @license GPL-2.0-or-later
	 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
	 */
	class SomeValue extends Variation {
		/**
		 * @inheritDoc
		 */
		setupVariation() {
			this.variationSnakConstructor = datamodel.PropertySomeValueSnak;
			this.variationBaseClass = 'wikibase-snakview-variation-somevaluesnak';
		}

		/**
		 * @inheritdoc
		 */
		draw() {
			// display same message in edit and non-edit mode!
			this.$viewPort.empty().append(
				$( '<span>' ) // this span is not in any template, see MessageSnakFormatter
					.addClass( 'wikibase-snakview-variation-somevaluesnak' )
					.text( mw.msg( 'wikibase-snakview-variations-somevalue-label' ) )
			);
			$( this ).trigger( 'afterdraw' );
		}
	}

	MODULE.registerVariation( 'somevalue', SomeValue );

}() );
