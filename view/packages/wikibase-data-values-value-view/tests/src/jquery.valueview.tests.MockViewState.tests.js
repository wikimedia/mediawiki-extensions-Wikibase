/**
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( QUnit, valueview ) {
	'use strict';

	var ViewState = valueview.ViewState,
		MockViewState = valueview.tests.MockViewState;

	QUnit.module( 'jquery.valueview.MockViewState' );

	/**
	 * Helper which returns a test function for a member of MockViewState.
	 *
	 * @param {string} memberName
	 * @return {Function}
	 */
	function buildMemberTestFn( memberName ) {
		return function( params, assert ) {
			assert.expect( 1 );
			var viewState = new MockViewState( params.constructorArg );

			assert.strictEqual(
				viewState[ memberName ](),
				params[ memberName ],
				'"' + memberName + '" returns injected value'
			);
		};
	}

	QUnit
	.cases( [
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
	] )
		.test( 'constructor', function( params, assert ) {
			assert.expect( 2 );
			var viewState = new MockViewState( params.constructorArg );

			assert.ok(
				viewState instanceof MockViewState,
				'MockViewState has been created successfully'
			);

			assert.ok(
				viewState instanceof ViewState,
				'Constructed MockViewState is instanceof ViewState'
			);
		} )
		.test( 'isInEditMode', buildMemberTestFn( 'isInEditMode' ) )
		.test( 'isDisabled', buildMemberTestFn( 'isDisabled' ) )
		.test( 'value', buildMemberTestFn( 'value' ) )
		.test( 'option', function( params, assert ) {
			assert.expect( 2 );
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

	QUnit.test( 'Changing state after construction', function( assert ) {
		assert.expect( 2 );
		var state = {},
			viewState = new MockViewState( state );

		assert.ok(
			!viewState.isInEditMode(),
			'MockViewState "isInEditMode" returns false after injecting empty definition'
		);

		state.isInEditMode = true;

		assert.ok(
			viewState.isInEditMode(),
			'MockViewState "isInEditMode" returns true after changing object given to constructor'
		);
	} );

}( QUnit, jQuery.valueview ) );
