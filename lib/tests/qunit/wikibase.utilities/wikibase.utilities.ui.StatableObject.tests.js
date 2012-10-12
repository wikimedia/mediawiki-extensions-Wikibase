/**
 * QUnit tests for wikibase.utilities.ui.StatableObject
 *
 * @since 0.2
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( mw, wb, $, QUnit, undefined ) {
	'use strict';

	QUnit.module( 'wikibase.utilities.ui.StatableObject', QUnit.newWbEnvironment( {
		setup: function() {},
		teardown: function() {}
	} ) );

	QUnit.test( 'Basic tests', function( assert ) {
		var StatableObj = function() {
			this.state = this.STATE.DISABLED;
		};

		// Define constructor implementing StatableObject:
		wb.utilities.ui.StatableObject.useWith( StatableObj, {
			getState: function() {
				return this.state;
			},
			_setState: function( state, failure ) {
				if( !failure ) {
					this.state = state;
					return true;
				} else {
					return false; // simulate no success
				}
			}
		} );

		assert.deepEqual(
			StatableObj.prototype.STATE,
			wb.utilities.ui.StatableObject.prototype.STATE,
			'New, with StatableObject extended constructor has STATE enum in its prototype'
		);

		var stateObj = new StatableObj(), // new instance using the constructor above
			STATE = stateObj.STATE; // shortcurt for getting states

		assert.deepEqual(
			STATE,
			StatableObj.prototype.STATE,
			'Instance of new StatableObject constructor has STATE enum'
		);

		/**
		 * Helper function for testing getState(), isEnabled() and isDisabled()
		 *
		 * @param String currentState
		 * @param String fnName name of the function to be tested
		 * @param Array args (optional) arguments for the function. Can be skipped.
		 * @param expected what the function should return
		 */
		var testStateFunction = function( currentState, fnName, args, expected ) {
			if( expected === undefined ) {
				expected = args;
				args = [];
			}
			currentState = ( currentState === STATE.ENABLED ) ? 'enabled' : 'disabled';
			assert.equal(
				stateObj[ fnName ].apply( stateObj, args || [] ),
				expected,
				fnName + '() works fine if object is ' + currentState
			);
		};

		// check initial state and basic functions
		testStateFunction( STATE.DISABLED, 'getState', STATE.DISABLED );
		testStateFunction( STATE.DISABLED, 'isDisabled', true );
		testStateFunction( STATE.DISABLED, 'isEnabled', false );

		// change state to disabled, then test basic functions again
		assert.equal(
			stateObj.setState( stateObj.STATE.ENABLED ),
			true,
			'Changed state to enabled, setState() returned true to imply success'
		);
		testStateFunction( STATE.ENABLED, 'getState', STATE.ENABLED );
		testStateFunction( STATE.ENABLED, 'isDisabled', false );
		testStateFunction( STATE.ENABLED, 'isEnabled', true );

		// test simulated blocker of setting state
		assert.ok(
			!stateObj.setState( STATE.DISABLED, true ),
			'Simulated blocking of state when setting state, setState() returns false as expected'
		);
		assert.equal(
			stateObj.getState(),
			STATE.ENABLED,
			'State is still enabled because setting state was blocked'
		);
	} );

	// Test errors:
	QUnit.test( 'Test without implementing abstract functions', function( assert ) {
		var StatableObj = function() {};

		wb.utilities.ui.StatableObject.useWith( StatableObj, {} );

		assert.throws(
			function() {
				StatableObj.getState();
			},
			'Abstract function wasn\'t implemented, throws error when calling getState()'
		);

		assert.throws(
			function() {
				StatableObj.setState( StatableObj.STATE.ENABLED );
			},
			'Abstract function wasn\'t implemented, throws error when calling setState()'
		);
	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
