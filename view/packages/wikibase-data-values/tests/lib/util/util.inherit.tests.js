/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
( function( define ) {
'use strict';

var DEPS = ['util.inherit', 'qunit'];

define( DEPS, function( util, QUnit ) {

	var namedFunction = function namedFunction() {};
	if( namedFunction.name !== 'namedFunction' ) {
		return; // Named functions are not supported by environment, so skip this test.
	}

	QUnit.module( 'util.inherit constructor names' );

	function inheritConstructorNameTest( description, testArguments, test ) {
		QUnit.test( description, function( assert ) {
			for( var i = 0; i < testArguments.length; i++ ) {
				test.apply( assert, testArguments[i] );
			}
		} );
	}

	inheritConstructorNameTest(
		'inherit( name, ... ) with legal names',
		[
			[ '$' ],
			[ '_' ],
			[ '$foo' ],
			[ '_foo' ],
			[ 'foo' ],
			[ 'foo42' ]
		],
		function( constructorName ) {
			this.equal(
				util.inherit( constructorName, Object ).name,
				constructorName,
				'inherit( \'' + constructorName + '\', ... ); creates constructor named as "' +
					constructorName + '".'
			);
		}
	);

	inheritConstructorNameTest(
		'inherit( name, ... ) with names which will be altered',
		[
			[ '42foo', 'foo' ],
			[ 'function()xxx(){};', 'functionxxx' ],
			[ 'xyz;${]123', 'xyz$123' ],
			[ ';a;1;$;b;_;', 'a1$b_' ],
			[ 'a1$b2c3d4;', 'a1$b2c3d4' ]
		],
		function( constructorName, expectedName ) {
			this.equal(
				util.inherit( constructorName, Object ).name,
				expectedName,
				'inherit( \'' + constructorName + '\', ... ); will use "' + expectedName +
					'" as name.'
			);
		}
	);

	inheritConstructorNameTest(
		'inherit( name, ... ) with illegal names',
		[
			[ '();' ],
			[ '42' ], // can't start function name with number
			[ 'class' ], // reserved word
			[ 'function' ] // reserved word
		],
		function( constructorName ) {
			this.throws(
				function() {
					util.inherit( constructorName, Object );
				},
				'inherit( \'' + constructorName + '\', ... ); will throw an error because of ' +
					'illegal constructor name.'
			);
		}
	);

} );

}( define ) );
