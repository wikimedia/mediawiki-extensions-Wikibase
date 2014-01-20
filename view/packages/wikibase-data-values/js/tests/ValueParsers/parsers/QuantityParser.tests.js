/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
valueParsers.tests.QuantityParserTest = ( function(
	ValueParserTest, DecimalValue, QuantityValue, QuantityParser, util
) {
	'use strict';

	var PARENT = ValueParserTest;
	var QuantityParserTest = util.inherit( PARENT, {
		/**
		 * @see vp.tests.ValueParserTest.getConstructor
		 */
		getConstructor: function() {
			return QuantityParser;
		},

		/**
		 * @see vp.tests.ValueParserTest.getParseArguments
		 */
		getParseArguments: function() {
			return [
				[
					'+0!',
					new QuantityValue(
						new DecimalValue( 0 ),
						'1',
						new DecimalValue( 0 ),
						new DecimalValue( 0 )
					)
				], [
					'+1!',
					new QuantityValue(
						new DecimalValue( 1 ),
						'1',
						new DecimalValue( 1 ),
						new DecimalValue( 1 )
					)
				], [
					'+1.5!',
					new QuantityValue(
						new DecimalValue( 1.5 ),
						'1',
						new DecimalValue( 1.5 ),
						new DecimalValue( 1.5 )
					)
				], [
					'-2!',
					new QuantityValue(
						new DecimalValue( -2 ),
						'1',
						new DecimalValue( -2 ),
						new DecimalValue( -2 )
					)
				], [
					'+100000000000000000000000000000!',
					new QuantityValue(
						new DecimalValue( 100000000000000000000000000000 ),
						'1',
						new DecimalValue( 100000000000000000000000000000 ),
						new DecimalValue( 100000000000000000000000000000 )
					)
				]
/* TODO: Activate after bug #56682 has been fixed
				, [
					'0+-1',
					new QuantityValue(
						new DecimalValue( 0 ),
						'1',
						new DecimalValue( -1 ),
						new DecimalValue( 1 )
					)
				], [
					'0±1',
					new QuantityValue(
						new DecimalValue( 0 ),
						'1',
						new DecimalValue( -1 ),
						new DecimalValue( 1 )
					)
				]
*/
			];
		}

	} );

	var test = new QuantityParserTest();
	test.runTests( 'valueParsers.QuantityParser' );

	return QuantityParserTest;

}(
	valueParsers.tests.ValueParserTest,
	dataValues.DecimalValue,
	dataValues.QuantityValue,
	valueParsers.QuantityParser,
	util
) );
