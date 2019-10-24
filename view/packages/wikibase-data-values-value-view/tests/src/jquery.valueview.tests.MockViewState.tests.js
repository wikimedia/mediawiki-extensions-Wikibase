/**
 * @license GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */

( function( QUnit, valueview ) {
	'use strict';

	var ViewState = require( '../../src/jquery.valueview.ViewState.js' ),
		MockViewState = valueview.tests.MockViewState;

	QUnit.module( 'jquery.valueview.MockViewState' );

	/**
	 * Helper which returns a test function for a member of MockViewState.
	 *
	 * @param {Object} params
	 * @param {string} memberName
	 * @return {Function}
	 */
	function buildMemberTestFn( params, memberName ) {
		return function( assert ) {
			var viewState = new MockViewState( params.constructorArg );

			assert.strictEqual(
				viewState[ memberName ](),
				params[ memberName ],
				'"' + memberName + '" returns injected value'
			);
		};
	}

	var testCases = [
		{
			title: 'without constructor argument',
			constructorArg: undefined,
			isInEditMode: false,
			isDisabled: false,
			value: undefined,
			optionFoo: undefined,
			optionBar: undefined
		}, {
			title: 'empty object as constructor argument',
			constructorArg: {},
			isInEditMode: false,
			isDisabled: false,
			value: undefined,
			optionFoo: undefined,
			optionBar: undefined
		}, {
			title: 'fully defined object with mixed definition',
			constructorArg: {
				isInEditMode: true,
				isDisabled: false,
				value: 'foo',
				options: {
					foo: true,
					bar: '42'
				}
			},
			isInEditMode: true,
			isDisabled: false,
			value: 'foo',
			optionFoo: true,
			optionBar: '42'
		}, {
			title: 'fully defined object with incomplete/weird definition',
			constructorArg: {
				isInEditMode: 'foo', // should result into true
				isDisabled: 'xxx', // should result into true
				options: {
					foo: true
				}
			},
			isInEditMode: true,
			isDisabled: true,
			value: undefined,
			optionFoo: true,
			optionBar: undefined
		}
	];

	testCases.forEach( function ( params ) {
		QUnit.test( 'constructor', function( assert ) {
			var viewState = new MockViewState( params.constructorArg );
			assert.ok(
				viewState instanceof MockViewState,
				'MockViewState has been created successfully'
			);

			assert.notEqual(
				viewState.getFormattedValue, 'undefined',
				'Constructed MockViewState is instanceof ViewState'
			);
		} );

		QUnit.test( 'isInEditMode', buildMemberTestFn( params, 'isInEditMode' ) );

		QUnit.test( 'isDisabled', buildMemberTestFn( params, 'isDisabled' ) );

		QUnit.test( 'value', buildMemberTestFn( params, 'value' ) );

		QUnit.test( 'option', function( assert ) {
			var viewState = new MockViewState( params.constructorArg );

			assert.strictEqual(
				viewState.option( 'foo' ),
				params.optionFoo,
				'Option "foo" holds injected value'
			);

			assert.strictEqual(
				viewState.option( 'bar' ),
				params.optionBar,
				'Option "bar" holds injected value'
			);
		} );
	} );

	QUnit.test( 'Changing state after construction', function( assert ) {
		var state = {},
			viewState = new MockViewState( state );

		assert.strictEqual(
			viewState.isInEditMode(), false,
			'MockViewState "isInEditMode" returns false after injecting empty definition'
		);

		state.isInEditMode = true;

		assert.strictEqual(
			viewState.isInEditMode(), true,
			'MockViewState "isInEditMode" returns true after changing object given to constructor'
		);
	} );

}( QUnit, jQuery.valueview ) );
