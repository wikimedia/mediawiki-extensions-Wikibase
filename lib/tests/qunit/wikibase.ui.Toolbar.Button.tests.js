/**
 * QUnit tests for Button prototype for toolbars
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater < mediawiki at snater.com >
 */

( function( wb, $, QUnit ) {
	'use strict';

	/**
	 * Factory for creating a new Toolbar.Button suited for testing. The button is returned in a div
	 * container since state changes will cause the button to alter its node structure which can
	 * cause problems e.g. when removing the button from the DOM.
	 *
	 * @param {String} [text] Button text
	 * @return {jQuery} Div element containing a button
	 */
	var newTestButton = function( text ) {
		if ( text === undefined ) {
			text = 'Text';
		}
		var button = new wb.ui.Toolbar.Button( text ),
			container = $( '<div/>' ).addClass( 'test_button' ).data( 'button', button );
		return container.append( button._elem );
	};

	QUnit.module( 'wikibase.ui.Toolbar.Button', QUnit.newWbEnvironment( {
		teardown: function() { $( '.test_button' ).remove(); }
	} ) );

	QUnit.test( 'Initialisation', function( assert ) {
		var buttonContainer = newTestButton( 'Button label' ),
			button = buttonContainer.data( 'button' );

		assert.ok(
			( typeof button._elem === 'object' ) && button.getContent() === 'Button label',
			'Button was initialised properly.'
		);
	} );

	QUnit.test( 'Button action', function( assert ) {
		var buttonContainer = newTestButton(),
			button = buttonContainer.data( 'button' );

		button.__test = false;

		$( button ).on( 'action', function( event ) {
			this.__test = true;
		} );

		assert.equal(
			button.doAction(),
			true,
			'Executed button action.'
		);

		assert.equal(
			button.__test,
			true,
			'Custom button action was executed.'
		);
	} );

	QUnit.test( 'Apply and remove focus', function( assert ) {
		var buttonContainer = newTestButton(),
			button = buttonContainer.data( 'button' );

		// attach button to body in order to be able to focus it
		$( 'body' ).append( buttonContainer );

		assert.ok(
			!button._elem.is( ':focus' ),
			'Button is not focused.'
		);

		button.setFocus();

		assert.ok(
			button._elem.is( ':focus' ),
			'Focused button.'
		);

		button.removeFocus();

		assert.ok(
			!button._elem.is( ':focus' ),
			'Removed focus from button.'
		);

		button.disable();

		assert.ok(
			button.isDisabled(),
			'Disabled button.'
		);

		button.setFocus();

		assert.ok(
			button._elem.is( ':focus' ),
			'Focused button.'
		);

		button.enable();

		assert.ok(
			button.isEnabled(),
			'Enabled button.'
		);

		assert.ok(
			button._elem.is( ':focus' ),
			'Still, button is focused.'
		);
	} );

}( wikibase, jQuery, QUnit ) );
