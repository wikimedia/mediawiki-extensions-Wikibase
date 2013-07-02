/**
 * QUnit tests for toolbar component
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater
 */

( function( mw, wb, $, QUnit ) {
	'use strict';

	module( 'wikibase.ui.Toolbar', QUnit.newWbEnvironment( {
		setup: function() {
			this.toolbar = new wb.ui.Toolbar();
		},
		teardown: function() {}

	} ) );

	QUnit.test( 'init and destroy', function( assert ) {

		assert.ok(
			this.toolbar._items instanceof Array,
			'initiated items array'
		);

		assert.equal(
			this.toolbar._elem[0].nodeName,
			'SPAN',
			'placed in DOM'
		);

		this.toolbar.destroy();

		assert.equal(
			this.toolbar._items,
			null,
			'destroyed items'
		);

		assert.equal(
			this.toolbar._elem,
			null,
			'destroyed _elem'
		);

	} );


	QUnit.test( 'fill and remove', function( assert ) {

		var label = $( '<span/>' ).wblabel( { content: 'label text' } );

		this.toolbar.addElement( label );

		assert.equal(
			this.toolbar.hasElement( label ),
			true,
			'added label to toolbar'
		);

		assert.equal(
			this.toolbar.getLength(), 1,
			'Toolbar length is 1 now'
		);

		this.toolbar.addElement( label );

		assert.equal(
			this.toolbar.getLength(), 1,
			'Toolbar length attribute still says one child after adding same label again'
		);


		var button = $( '<a/>' ).text( 'button text' ).wbbutton();

		this.toolbar.addElement( button );

		assert.equal(
			this.toolbar.hasElement( button ),
			true,
			'added a button to toolbar'
		);

		assert.equal(
			this.toolbar.getLength(), 2,
			'Toolbar length is 2 now'
		);

		assert.equal(
			this.toolbar.getIndexOf( button ),
			1,
			'correctly polled index of button'
		);

		assert.equal(
			this.toolbar.getElements().length,
			2,
			'two elements added overall'
		);

		var second_label = $( '<span/>' ).wblabel( { content: 'second label text' } );

		this.toolbar.addElement( second_label, 1 );

		assert.equal(
			this.toolbar.hasElement( second_label ),
			true,
			'inserted a label as second element to toolbar'
		);

		assert.equal(
			this.toolbar.getLength(), 3,
			'Toolbar length is 3 now'
		);

		assert.equal(
			this.toolbar.getIndexOf( second_label ),
			1,
			'correctly polled index of label'
		);

		assert.equal(
			this.toolbar._elem.children().length,
			3,
			'checked DOM: toolbar element has 3 children'
		);

		assert.equal(
			this.toolbar.removeElement( second_label ),
			true,
			'removed second label'
		);

		assert.equal(
			this.toolbar.getLength(), 2,
			'Toolbar length is 2 now'
		);

		assert.equal(
			this.toolbar._elem.children().length,
			2,
			'checked DOM: toolbar element has 2 two children'
		);

		assert.equal(
			this.toolbar.getIndexOf( second_label ),
			-1,
			'second label not referenced anymore'
		);

		assert.equal(
			this.toolbar.hide(),
			true,
			'hide toolbar'
		);

		assert.equal(
			this.toolbar.show(),
			true,
			'show toolbar'
		);

	} );

	QUnit.test( 'dis- and enabling', function( assert ) {
		var label = $( '<span/>' ).wblabel( { content: 'label text', stateChangeable: false } );
		this.toolbar.addElement( label );

		assert.equal(
			this.toolbar.isStateChangeable(),
			false,
			'toolbar state is not changeable (no elements that are changeable)'
		);

		var button = $( '<a/>' ).text( 'button text' ).wbbutton();
		this.toolbar.addElement( button );

		assert.equal(
			this.toolbar.isStateChangeable(),
			true,
			'toolbar state is changeable'
		);

		assert.equal(
			this.toolbar.disable(),
			true,
			'disabling toolbar'
		);

		assert.equal(
			this.toolbar.isDisabled(),
			true,
			'toolbar is disabled'
		);

		label.data( 'wblabel' ).option( 'stateChangeable', true );

		assert.equal(
			this.toolbar.getState(),
			wb.utilities.ui.StatableObject.prototype.STATE.MIXED,
			'mixed state after making label changeable'
		);

		assert.equal(
			this.toolbar.isDisabled(),
			false,
			'toolbar is not disabled (since element states are mixed)'
		);

		assert.equal(
			this.toolbar.isEnabled(),
			false,
			'toolbar is not enabled (since element states are mixed)'
		);

		assert.equal(
			this.toolbar.enable(),
			true,
			'enabling toolbar'
		);

		assert.equal(
			this.toolbar.isEnabled(),
			true,
			'toolbar is enabled'
		);

		assert.equal(
			this.toolbar.disable(),
			true,
			'disabling toolbar'
		);

		assert.equal(
			this.toolbar.isDisabled(),
			true,
			'toolbar is disabled'
		);

		assert.equal(
			this.toolbar.isEnabled(),
			false,
			'toolbar is not enabled'
		);

	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
