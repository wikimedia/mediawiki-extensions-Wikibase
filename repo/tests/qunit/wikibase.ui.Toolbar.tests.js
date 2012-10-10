/**
 * QUnit tests for toolbar component
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
'use strict';


( function() {
	module( 'wikibase.ui.Toolbar', window.QUnit.newWbEnvironment( {
		setup: function() {
			this.toolbar = new window.wikibase.ui.Toolbar();

			ok(
				this.toolbar._items instanceof Array,
				'initiated items array'
			);

			equal(
				this.toolbar._elem[0].nodeName,
				'DIV',
				'placed in DOM'
			);

		},
		teardown: function() {
			this.toolbar.destroy();

			equal(
				this.toolbar._items,
				null,
				'destroyed items'
			);

			equal(
				this.toolbar._elem,
				null,
				'destroyed _elem'
			);

		}

	} ) );


	test( 'fill and remove', function() {

		var label = new window.wikibase.ui.Toolbar.Label( 'label text' );

		this.toolbar.addElement( label );

		equal(
			this.toolbar.hasElement( label ),
			true,
			'added label to toolbar'
		);

		var button = new window.wikibase.ui.Toolbar.Button( 'button text' );

		this.toolbar.addElement( button );

		equal(
			this.toolbar.hasElement( button ),
			true,
			'added a button to toolbar'
		);

		equal(
			this.toolbar.getIndexOf( button ),
			1,
			'correctly polled index of button'
		);

		equal(
			this.toolbar.getElements().length,
			2,
			'two elements added overall'
		);

		var second_label = new window.wikibase.ui.Toolbar.Label( 'second label text' );

		this.toolbar.addElement( second_label, 1 );

		equal(
			this.toolbar.hasElement( second_label ),
			true,
			'inserted a label as second element to toolbar'
		);

		equal(
			this.toolbar.getIndexOf( second_label ),
			1,
			'correctly polled index of label'
		);

		equal(
			this.toolbar._elem.children().length,
			3,
			'checked DOM: toolbar element has 3 children'
		);

		equal(
			this.toolbar.removeElement( second_label ),
			true,
			'removed second label'
		);

		equal(
			this.toolbar._elem.children().length,
			2,
			'checked DOM: toolbar element has 2 two children'
		);

		equal(
			this.toolbar.getIndexOf( second_label ),
			-1,
			'second label not referenced anymore'
		);

		equal(
			this.toolbar.hide(),
			true,
			'hide toolbar'
		);

		equal(
			this.toolbar.show(),
			true,
			'show toolbar'
		);

	} );

	test( 'dis- and enabling', function() {
		var label = new window.wikibase.ui.Toolbar.Label( 'label text' );
		label.stateChangeable = false;
		this.toolbar.addElement( label );

		equal(
			this.toolbar.isStateChangeable(),
			false,
			'toolbar state is not changeable (no elements that are changeable)'
		);

		var button = new window.wikibase.ui.Toolbar.Button( 'button text' );
		this.toolbar.addElement( button );

		equal(
			this.toolbar.isStateChangeable(),
			true,
			'toolbar state is changeable'
		);

		equal(
			this.toolbar.disable(),
			true,
			'disabling toolbar'
		);

		equal(
			this.toolbar.isDisabled(),
			true,
			'toolbar is disabled'
		);

		label.stateChangeable = true;

		equal(
			this.toolbar.getState(),
			wb.utilities.ui.StatableObject.prototype.STATE.MIXED,
			'mixed state after making label changeable'
		);

		equal(
			this.toolbar.isDisabled(),
			false,
			'toolbar is not disabled (since element states are mixed)'
		);

		equal(
			this.toolbar.isEnabled(),
			false,
			'toolbar is not enabled (since element states are mixed)'
		);

		equal(
			this.toolbar.enable(),
			true,
			'enabling toolbar'
		);

		equal(
			this.toolbar.isEnabled(),
			true,
			'toolbar is enabled'
		);

		equal(
			this.toolbar.disable(),
			true,
			'disabling toolbar'
		);

		equal(
			this.toolbar.isDisabled(),
			true,
			'toolbar is disabled'
		);

		equal(
			this.toolbar.isEnabled(),
			false,
			'toolbar is not enabled'
		);

	} );

}() );
