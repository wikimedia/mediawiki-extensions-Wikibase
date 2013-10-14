/**
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
( function( dv, $, QUnit ) {
	'use strict';

	QUnit.module( 'dataValues.util.inherit constructor names' );

	QUnit.test( 'inherit( name, ... ) with different names', function( assert ) {
		var names = [
			[ '$' ],
			[ '_' ],
			[ '$foo' ],
			[ '_foo' ],
			[ 'foo' ],
			[ 'foo42' ],
			[ '42foo', 'foo' ],
			[ 'function()xxx(){};', 'functionxxx' ],
			[ 'xyz;${]123', 'xyz$123' ],
			[ ';a;1;$;b;_;', 'a1$b_' ],
			[ 'a1$b2c3d4;', 'a1$b2c3d4' ],
			[ '();', false ],
			[ '42', false ], // can't start function name with number
			[ 'class', false ], // reserved word
			[ 'function', false ] // reserved word
		];

		$.each( names, function( i, definition ) {
			var testName = definition[0],
				expectedName =  definition[1] || ( definition[1] === undefined ? testName : false );

			if( !expectedName ) {
				assert.throws(
					function() {
						dv.util.inherit( testName, Object );
					},
					'inherit( \'' + testName + '\', ... ); will throw and error because of malicious constructor name.'
				);
			} else {
				assert.equal(
					dv.util.inherit( testName, Object ).name,
					expectedName,
					'inherit( \'' + testName + '\', ... ); will use "' + expectedName + '" as name.'
				);
			}

		} );
	} );

}( dataValues, jQuery, QUnit ) );
