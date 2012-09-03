/**
 * QUnit tests for Wikibase inherit() function for more prototypal inheritance convenience.
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, $, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.utilities.inherit', QUnit.newWbEnvironment( {
		setup: function() {},
		teardown: function() {}
	} ) );

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
		var c;
		if( constructor === null && members === null ) {
			// only base is given:
			c = wb.utilities.inherit( base );
		}
		else if( constructor === null ) {
			// constructor omitted, check for right param mapping:
			c =      wb.utilities.inherit( base, members );
			var c2 = wb.utilities.inherit( base, function() {}, members );

			assert.deepEqual(
				c.prototype,
				c2.prototype,
				'inherit() works as expected if "constructor" parameter was omitted.'
			);
			c2 = null;
		}
		else if( members === null ) {
			c = wb.utilities.inherit( base, constructor );
		}
		else {
			c = wb.utilities.inherit( base, constructor, members );
		}

		assert.ok(
			$.isFunction( c ),
			'inherit() returned constructor'
		);

		if( constructor !== null ) {
			assert.equal(
				c,
				constructor,
				'prototypes constructor property is set to given constructor'
			);
		}

		if( members !== null ) {
			assert.deepEqual(
				c.prototype,
				( members !== null ? members : {} ),
				'Prototype of returned constructor has all extension properties set'
			);
		}
		return c;
	},

	inheritMembers = {
		i: 0,
		increase: function() { this.i++; },
		foo: 'baa'
	},

	inheritConstructor = function() { this.foo = 'test'},

	inheritConstructorTest = function( constructor ) {
		assert.ok(
			( new constructor() ).foo === 'test',
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
		var c = inheritTest( Object, inheritConstructor, null );
		inheritConstructorTest( c );
	} );

	QUnit.test( 'inherit( base, constructor, members )', function( assert ) {
		// both:
		var c = inheritTest( Object, inheritConstructor, inheritMembers );
		inheritConstructorTest( c );
	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
