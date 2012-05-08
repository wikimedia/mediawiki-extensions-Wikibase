/**
 * QUnit tests for toolbar component
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file wikibase.ui.Toolbar.tests.js
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */
'use strict';


( function() {
	module( 'wikibase.ui.Toolbar', window.QUnit.newWbEnvironment( null, null, {
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
	} );


}() );
