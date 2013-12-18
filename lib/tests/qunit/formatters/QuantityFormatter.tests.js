/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, vf, dv ) {
	'use strict';

	var PARENT = vf.tests.ValueFormatterTest;

	/**
	 * Constructor for creating a test object containing tests for the QuantityFormatter.
	 *
	 * @constructor
	 * @extends valueFormatters.tests.ValueFormatterTest
	 * @since 0.1
	 */
	wb.tests.QuantityFormatterTest = vf.util.inherit( PARENT, {

		/**
		 * @see valueFormatters.tests.ValueFormatterTest.getConstructor
		 */
		getConstructor: function() {
			return wb.formatters.QuantityFormatter;
		},

		/**
		 * @see valueFormatters.tests.ValueFormatterTest.getFormatArguments
		 */
		getFormatArguments: function() {
			return [
				[
					new dv.QuantityValue(
						new dv.DecimalValue( 5 ),
						'some unit',
						new dv.DecimalValue( 6 ),
						new dv.DecimalValue( 4 )
					),
					'5±1some unit'
				]
			];
		}

	} );

	var test = new wb.tests.QuantityFormatterTest();

	test.runTests( 'wikibase.formatters.QuantityFormatter' );

}( wikibase, valueFormatters, dataValues ) );
