/**
 * @license GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */
( function( Notifier, $, QUnit ) {
	'use strict';

	QUnit.module( 'util.Notifier' );

	QUnit.test( 'Construction of Notifier instances', function( assert ) {
		var n;

		assert.ok(
			new Notifier() instanceof Notifier,
			'Instance created without using "new" keyword'
		);

		assert.ok(
			new Notifier() instanceof Notifier,
			'Instance created by using "new" keyword'
		);

		assert.ok(
			new Notifier( {} ) instanceof Notifier,
			'Instance created with empty object literal as argument'
		);

		assert.throws(
			function() {
				n = new Notifier( 'foo' );
			},
			'Creating Notifier with wrong argument fails'
		);
	} );

	QUnit.test( 'Notifier.prototype.notify', function( assert ) {
		var notifier;

		// callback with tests for notify() calls:
		var fnNotifyAssertions = function( testNotificationKeyName ) {
			assert.ok(
				true,
				'notification has been triggered'
			);

			assert.strictEqual(
				this, notifier,
				'Context the notify callback is called in is the Notifier object'
			);

			assert.notStrictEqual(
				testNotificationKeyName, undefined,
				'Custom notify argument got passed into callback'
			);

			assert.strictEqual(
				this.current(),
				testNotificationKeyName,
				'Notifier.current() returns callback notification key "' + testNotificationKeyName + '"'
			);
		};

		notifier = new Notifier( {
			test: fnNotifyAssertions,
			test2: fnNotifyAssertions
		} );

		assert.strictEqual(
			notifier.current(), null,
			'Notifier.current() returns null'
		);

		notifier.notify( 'test', [ 'test' ] );
		notifier.notify( 'test2', [ 'test2' ] );
		notifier.notify( 'test3' ); // should not do anything

		assert.strictEqual(
			notifier.current(), null,
			'Notifier.current() returns null again'
		);
	} );

}( util.Notifier, jQuery, QUnit ) );
