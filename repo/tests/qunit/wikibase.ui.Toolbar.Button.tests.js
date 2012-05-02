/**
 * QUnit tests for Button prototype for toolbars
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.Toolbar.Button.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
'use strict';

( function () {
	module( 'wikibase.ui.Toolbar.Button', {
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

	} );

	test( 'button action', function() {

		this.button.onAction = function() { this.__test = true };

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

		this.button.onAction = function() { return false };

		equal(
			this.button.doAction(),
			false,
			'execute button action but action returns false, not executed'
		);

		this.button.onAction = function() { return true };

		equal(
			this.button.doAction(),
			true,
			'button action executed, onAction returned true'
		);

	} );

}() );
