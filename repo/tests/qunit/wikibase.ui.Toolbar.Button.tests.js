/**
 * QUnit tests for Button prototype for toolbars
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.Toolbar.Button.tests.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
'use strict';

( function () {
	module( 'wikibase.ui.Toolbar.Button', window.QUnit.newWbEnvironment( {
		setup: function() {

			this.button = new wikibase.ui.Toolbar.Button( 'Text' );

			ok(
				( typeof this.button._elem == 'object' ) && this.button.getContent() == 'Text',
				'Button was initialized properly'
			);

		},
		teardown: function() {
			this.button.destroy();

			equal(
				this.button._elem,
				null,
				'destroyed button'
			);
		}

	} ) );

	test( 'button action', function() {

		$( this.button ).on( 'action', function( event ) {
			this.__test = true;
		} );

		equal(
			this.button.doAction(),
			true,
			'execute button action'
		);

		equal(
			this.button.__test,
			true,
			'check whether custom button action was executed'
		);

	} );

	test( 'set focus', function() {

		// attach button to body in order to be able to focus it
		$( 'body' ).append( this.button._elem );

		ok(
			document.activeElement !== this.button._elem[0],
			'button is not focussed'
		);

		this.button.setFocus();

		equal(
			document.activeElement,
			this.button._elem[0],
			'set focus on button'
		);

		this.button.removeFocus();

		ok(
			document.activeElement !== this.button._elem[0],
			'removed focus from button'
		);

	} );

}() );
