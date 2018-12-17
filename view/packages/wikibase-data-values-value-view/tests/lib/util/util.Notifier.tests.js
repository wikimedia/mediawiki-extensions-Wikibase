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

	QUnit.test( 'Notifier.prototype.hasListenerFor', function( assert ) {
		var notificationKeys = [ 'foo', 'bar123', 'xxx' ],
			notificationMap = {};

		$.map( notificationKeys, function( val, i ) {
			notificationMap[ val ] = $.noop;
		} );

		var notifier = new Notifier( notificationMap ),
			emptyNotifier = new Notifier( {} );

		// check whether all notification keys are available on the Notifier object:
		$.each( notificationKeys, function( i, value ) {
			assert.strictEqual(
				notifier.hasListenerFor( value ), true,
				'Notifier has registered callback for notification "' + value + '"'
			);
			assert.strictEqual(
				emptyNotifier.hasListenerFor( value ), false,
				'Empty Notifier does not have callback for notification "' + value + '"'
			);
		} );
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
