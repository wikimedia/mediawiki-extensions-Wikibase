/**
 * QUnit tests for ObservableObject extension object
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase.utilities.ObservableObject', QUnit.newWbEnvironment( {
		setup: function() {},
		teardown: function() {}
	} ) );

	// test with members only:
	QUnit.test( 'Simple event handling', function( assert ) {
		var obj = new wb.utilities.ObservableObject(),
			triggered = false;

		function triggerCallback() {
			triggered = true;
		}

		function triggerTest() {
			triggered = false;
			obj.trigger( 'test' );
			return triggered;
		}

		obj.on( 'test', triggerCallback );
		assert.ok(
			triggerTest(),
			'ObservableObject.trigger() and .on() are working properly'
		);

		obj.off( 'test' );
		assert.ok(
			!triggerTest(),
			'after .off( eventName ), .trigger() won\'t fire event again'
		);

		obj.on( 'test', triggerCallback );
		assert.ok(
			triggerTest(),
			'Event registered again'
		);

		obj.off( 'test', function() {} );
		assert.ok(
			triggerTest(),
			'calling .off() with some callback which is not used, shouldn\'t remove the callback which was set'
		);

		obj.off( 'test', triggerCallback );
		assert.ok(
			!triggerTest(),
			'after .off( eventName, eventCallBack ), .trigger() won\'t fire callback again'
		);

		obj.one( 'test', triggerCallback );
		assert.ok(
			triggerTest(),
			'After setting event with .one(), .trigger() will fire event'
		);

		assert.ok(
			!triggerTest(),
			'Second trigger won\'t fire anymore because .one() has been used instead of .on()'
		);
	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
