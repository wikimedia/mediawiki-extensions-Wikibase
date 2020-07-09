( function () {
	'use strict';

	var MODULE = require( './snakview.variations.js' ),
		PARENT = require( './snakview.variations.Variation.js' ),
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
	 *
	 * @constructor
	 */
	MODULE.variation( datamodel.PropertySomeValueSnak, PARENT, {
		/**
		 * @inheritdoc
		 */
		draw: function () {
			// display same message in edit and non-edit mode!
			this.$viewPort.empty().append(
				$( '<span>' ) // this span is not in any template, see MessageSnakFormatter
					.addClass( 'wikibase-snakview-variation-somevaluesnak' )
					.text( mw.msg( 'wikibase-snakview-variations-somevalue-label' ) )
			);
			$( this ).trigger( 'afterdraw' );
		}
	} );

}() );
