/**
 * @license GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
( function( $, QUnit ) {
	'use strict';
	/* jshint newcap: false */

	var PurposedCallbacks = require( '../../../lib/jquery/jquery.PurposedCallbacks.js' );

	QUnit.module( 'jquery.PurposedCallbacks' );

	QUnit.test( 'construction', function( assert ) {
		var pc = PurposedCallbacks();

		assert.ok(
			pc instanceof PurposedCallbacks,
			'Instantiated without "new".'
		);
	} );

	QUnit.test( 'facade()', function( assert ) {
		var pc = PurposedCallbacks();

		assert.ok(
			pc.facade() instanceof PurposedCallbacks.Facade,
			'Returns instance of PurposedCallbacks.Facade.'
		);

		assert.strictEqual(
			pc.facade(), pc.facade(),
			'Always returns the same facade instance and does not create a new one.'
		);
	} );

	QUnit.test( 'puposes() on instance without predefined purposes', function( assert ) {
		var pcf = PurposedCallbacks().facade();

		assert.ok(
			Array.isArray( pcf.purposes() ) && pcf.purposes().length === 0,
			'Returns an empty array.'
		);

		pcf.add( 'foo', $.noop );
		pcf.purposes().length = 0;
		assert.deepEqual(
			pcf.purposes(),
			[ 'foo' ],
			'Returns a newly added purpose "foo", modifying the returned array has no effect ' +
				'on next returned array (no reference to internal object).'
		);

		pcf.add( 'bar', $.noop );
		assert.deepEqual(
			pcf.purposes(),
			[ 'foo', 'bar' ],
			'Returns a newly added purpose "bar" and the old "foo".'
		);

		pcf.remove( 'foo', $.noop );
		assert.deepEqual(
			pcf.purposes(),
			[ 'foo', 'bar' ],
			'Still returns "foo" as known purpose after removing only callback of "foo".'
		);
	} );

	QUnit.test( 'purposes() on instance with predefined purposes', function( assert ) {
		var purposes = [ 'foo', 'bar' ];
		var pcf = PurposedCallbacks( purposes ).facade();

		assert.deepEqual(
			pcf.purposes(),
			purposes,
			'Returns all predefined purposes initially.'
		);

		assert.notStrictEqual(
			pcf.purposes(), purposes,
			'Returned array is a copy of the predefined purposes, not the same object.'
		);

		pcf.add( 'foo', $.noop );
		pcf.remove( 'foo', $.noop );
		assert.deepEqual(
			pcf.purposes(),
			purposes,
			'Still returns all purposes after adding and removing a callback .'
		);
	} );

	QUnit.test( 'chainable members', function( assert ) {
		var pc = PurposedCallbacks();
		var pcf = pc.facade();

		assert.strictEqual(
			pcf.add( 'foo', $.noop ), pcf,
			'add() is chainable.'
		);
		assert.strictEqual(
			pcf.remove( 'foo', $.noop ), pcf,
			'remove() is chainable.'
		);

		// Chainable on base only, not a member of the facade:
		assert.ok(
			pc.fire( 'foo' ),
			'fire() is chainable'
		);
		assert.ok(
			pc.fireWith( 'foo', this ),
			'fireWith() is chainable'
		);
	} );

	QUnit.test( 'add() callback of unknown purpose', function( assert ) {
		var pcf = PurposedCallbacks( [ 'bar' ] ).facade();
		assert.throws(
			function() {
				pcf.add( 'foo', $.noop );
			},
			'Can not add callback for purpose not stated in list of predefined purposes.'
		);
	} );

	QUnit.test( 'fire()', function( assert ) {
		var pc = PurposedCallbacks();
		var pcf = pc.facade();

		var fired1 = 0;
		var fired2 = 0;
		var resetFired = function() {
			fired1 = fired2 = 0;
		};

		assert.strictEqual(
			pcf.fire, undefined,
			'fire() is not a member of the facade.'
		);

		pcf.add( '1', function() {
			fired1++;
		} );
		pcf.add( '2', function() {
			fired2++;
		} );

		pc.fire( '1' );
		assert.ok(
			fired1 === 1 && fired2 === 0,
			'Fired callback of stated purpose. Purpose can be given as string.'
		);

		resetFired();

		pc.fire( [ '2' ] );
		assert.ok(
			fired1 === 0 && fired2 === 1,
			'Fired callback of another stated purpose. Purpose can be given as array of strings.'
		);

		resetFired();

		pc.fire( [ '1', '2' ] );
		assert.ok(
			fired1 === 1 && fired2 === 1,
			'Fired callback of both stated purposes together'
		);
	} );

	QUnit.test( 'fire() with custom arguments', function( assert ) {
		var pc = PurposedCallbacks();
		var pcf = pc.facade();
		var args = [ {}, [] ];

		pcf.add( 'foo', function( arg1, arg2 ) {
			assert.ok(
				arg1 === args[0] && arg2 === args[1],
				'Arguments get passed to the callback.'
			);
		} );
		pc.fire( 'foo', args );
	} );

	// Helper callbacks for the following tests:
	var fn1 = function() {
		this.push( 1 );
	};
	var fn2 = function() {
		this.push( 2 );
	};
	var fn3 = function() {
		this.push( 3 );
	};
	var fn4 = function() {
		this.push( 4 );
	};

	QUnit.test( 'add() verified by fireWith()', function( assert ) {
		var pc = PurposedCallbacks();
		var pcf = pc.facade();

		pcf.add( 'foo', fn1 );
		pcf.add( 'bar', [ fn2, fn3 ] );
		pcf.add( 'foo', fn4 );

		var feedback = [];
		pc.fireWith( feedback, [ 'foo', 'bar' ] );
		assert.deepEqual(
			feedback,
			[ 1, 4, 2, 3 ],
			'Executed all callbacks in expected order. Verified add with single function and ' +
				'with array of functions.'
		);

		feedback = [];
		pc.fireWith( feedback, [ 'bar', 'foo' ] );
		assert.deepEqual(
			feedback,
			[ 2, 3, 1, 4 ],
			'Executed all callbacks in expected order. Verified callbacks of first purpose given ' +
				'in "fireWith" are fired first.'
		);
	} );

	QUnit.test( 'remove() verified by fireWith()', function( assert ) {
		var pc = PurposedCallbacks();
		var pcf = pc.facade();

		pcf.add( 'foo', fn1 );
		pcf.add( 'foo', fn2 );
		pcf.remove( 'foo', fn2 );
		pcf.add( 'foo', fn3 );
		pcf.add( 'bar', fn4 );
		pcf.remove( 'foo', fn4 ); // Not registered for the purpose, so fn4 should still be called.
		pcf.add( 'bar', fn1 );

		var feedback = [];
		pc.fireWith( feedback, [ 'foo', 'bar' ] );
		assert.deepEqual(
			feedback,
			[ 1, 3, 4, 1 ],
			'Executed all callbacks registered for purpose, except the ones removed again.'
		);

		feedback = [];
		pcf.remove( 'bar', [ fn1, fn4 ] );
		pc.fireWith( feedback, [ 'foo', 'bar' ] );
		assert.deepEqual(
			feedback,
			[ 1, 3 ],
			'remove() can remove all callbacks given in an array.'
		);
	} );

	QUnit.test( 'has()', function( assert ) {
		var pc = PurposedCallbacks();
		var pcf = pc.facade();

		pcf.add( 'foo', $.noop );
		assert.strictEqual(
			pcf.has( 'foo' ), true,
			'Newly defined purpose exists.'
		);
		assert.strictEqual(
			pcf.has( 'foo', $.noop ), true,
			'Callback for that purpose is recognized as existent as well.'
		);
		assert.strictEqual(
			pcf.has( 'bar' ), false,
			'Purpose without callbacks does not exist.'
		);

		pcf.remove( 'foo', $.noop );
		assert.strictEqual(
			pcf.has( 'foo' ), true,
			'Purpose still exists after removing only callback from it.'
		);
	} );

	QUnit.test( 'has() on instance with predefined purposes', function( assert ) {
		var pc = PurposedCallbacks( [ 'foo' ] );
		var pcf = pc.facade();

		assert.strictEqual(
			pcf.has( 'foo' ), true,
			'Predefined purpose exists.'
		);

		pcf.add( 'foo', $.noop );
		pcf.remove( 'foo', $.noop );
		assert.strictEqual(
			pcf.has( 'foo' ), true,
			'Predefined purpose exists still exists after adding and removing callback from it.'
		);
		assert.strictEqual(
			pcf.has( 'foo', $.noop ), false,
			'Callback got removed though.'
		);

		assert.strictEqual(
			pcf.has( 'bar' ), false,
			'Purpose without callbacks does not exist.'
		);
	} );

}( jQuery, QUnit ) );
