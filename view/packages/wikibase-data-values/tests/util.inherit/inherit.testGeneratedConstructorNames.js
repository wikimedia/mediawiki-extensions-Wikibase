/**
 * @since 0.1
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
( function( inherit, $, QUnit ) {
	'use strict';

	var namedFunction = function namedFunction() {};
	if( namedFunction.name !== 'namedFunction' ) {
		return; // Named functions are not supported by environment, so skip this test.
	}

	QUnit.module( 'dataValues.util.inherit implicit/generated constructor names' );

	var namedFunction = function namedFunction() {};

	QUnit.test( 'Constructor name taken from given function', function( assert ) {
		assert.strictEqual(
			inherit( Object, namedFunction ).name,
			namedFunction.name,
			'inherit( ... ) with a named function as constructor preserves given function\'s ' +
				'name as final constructor name.'
		);
	} );

	QUnit.test( 'Auto generate names based on base constructor as fallback', function( assert ) {
		var namedConstructor = inherit( Object, namedFunction );
		var namedConstructorDescendant = inherit( namedConstructor );

		assert.ok(
			namedConstructorDescendant.name.indexOf( namedConstructor.name ) === 0
				&& namedConstructorDescendant.name.length > namedConstructor.name.length,
			'Constructor returned by inherit() uses name of given constructor plus suffix'
		);
	} );

	QUnit.test( 'Explicitly given constructor names will prevail', function( assert ) {
		var namedConstructor = inherit( Object, namedFunction );

		assert.equal(
			inherit( 'someName', namedConstructor ).name,
			'someName',
			'If named constructor used as base but name for new constructor explicitly given, ' +
				'the the given name will be used.'
		);

		assert.equal(
			inherit( namedConstructor, namedFunction ).name,
			'namedFunction',
			'If named constructor used as base but named function given as constructor action, ' +
				'then the latter will be used as name.'
		);

		assert.equal(
			inherit( 'someName', namedConstructor, namedFunction ).name,
			'someName',
			'If named constructor used as base and named function given as constructor action ' +
				'but name for new constructor explicitly given, then the the given name will ' +
				'be used.'
		);
	} );

}( dataValues.util.inherit, jQuery, QUnit ) );
