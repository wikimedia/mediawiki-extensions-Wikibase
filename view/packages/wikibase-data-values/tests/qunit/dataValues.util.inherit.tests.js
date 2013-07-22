/**
 * QUnit tests for inherit() function for more prototypical inheritance convenience.
 *
 * @since 0.1
 * @file
 * @ingroup DataValues
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( dv, $, QUnit ) {
	'use strict';

	QUnit.module( 'dataValues.util.inherit' );

	// ============================
	// ======= Test Utils: ========
	// ============================

var // the following are a couple of var definitions used by the tests beyond
	/**
	 * Takes three parameters which match the three parameters of the inherit() function. If one should be
	 * omitted, null should be given.
	 *
	 * @param base Function
	 * @param constructor Function|null
	 * @param members Object|null
	 */
	inheritTest = function( base, constructor, members ) {
		var C;
		if( constructor === null && members === null ) {
			// only base is given:
			C = dv.util.inherit( base );
		}
		else if( constructor === null ) {
			// constructor omitted, check for right param mapping:
			C = dv.util.inherit( base, members );
			var C2 = dv.util.inherit( base, C.prototype.constructor, members ),
				origConstructorOfC = C.prototype.constructor;

			// constructors will never be the same since a new function will be created in inherit(),
			// so we have to set them to the same to test for all the other prototype members.
			C.prototype.constructor = null;
			C2.prototype.constructor = null;

			QUnit.assert.deepEqual(
				C.prototype,
				C2.prototype,
				'inherit() works as expected if "constructor" parameter was omitted.'
			);
			C2 = null;
			C.prototype.constructor = origConstructorOfC;
		}
		else if( members === null ) {
			C = dv.util.inherit( base, constructor );
		}
		else {
			C = dv.util.inherit( base, constructor, members );
		}

		QUnit.assert.ok(
			$.isFunction( C ),
			'inherit() returned constructor'
		);

		QUnit.assert.ok(
			( new C() ) instanceof base && ( new C() ) instanceof C,
			'"instanceof" is working like it should'
		);

		var proto = $.extend( {}, C.prototype );
		if( members === null || !members.hasOwnProperty( 'constructor' ) ) {
			delete( proto.constructor ); // constructor is an extra thing, set by inherit()
		}
		if( members !== null ) {
			QUnit.assert.deepEqual(
				proto,
				( members !== null ? members : {} ),
				'Prototype of returned constructor has all extension properties set'
			);
		}
		return C;
	},

	inheritMembers = {
		i: 0,
		increase: function() { this.i++; },
		foo: 'baa'
	},

	inheritConstructor = function InheritTestConstructor() {
		this.foo = 'test';
	},

	inheritConstructorTest = function( Constructor ) {
		QUnit.assert.ok(
			( new Constructor() ).foo === 'test',
			'Overwritten constructor is called'
		);
	};


	// ==============================
	// ======= Actual Tests: ========
	// ==============================

	QUnit.test( 'inherit( base )', function( assert ) {
		// members only:
		inheritTest( Object, null, null );
	} );

	QUnit.test( 'inherit( base, members )', function( assert ) {
		// members only:
		inheritTest( Object, null, inheritMembers );
	} );

	QUnit.test( 'inherit( base, constructor )', function( assert ) {
		// constructor only:
		var C1 = inheritTest( Object, inheritConstructor, null );
		inheritConstructorTest( C1 );

		QUnit.assert.equal(
			C1.name,
			inheritConstructor.name,
			'Constructor returned by inherit() takes name of given constructor function'
		);

		// inherit from C2:
		var C2 = inheritTest( C1, null, inheritMembers );
		inheritConstructorTest( C2 );

		QUnit.assert.ok(
			C2.name.indexOf( inheritConstructor.name ) === 0,
			'Constructor returned by inherit() uses name of given constructor plus suffix'
		);
	} );

	QUnit.test( 'inherit( base, constructor, members )', function( assert ) {
		// both:
		var C = inheritTest( Object, inheritConstructor, inheritMembers );
		inheritConstructorTest( C );
	} );

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
