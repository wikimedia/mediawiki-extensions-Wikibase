/**
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( mw, wb, $, QUnit ) {
	'use strict';

	QUnit.module( 'jQuery.wikibase.toolbar', QUnit.newWbEnvironment( {} ) );


	QUnit.test( 'Init and destroy', function( assert ) {
		var $toolbar = $( '<span/>' ).toolbar(),
			toolbar = $toolbar.data( 'toolbar' );

		assert.ok(
			toolbar._items instanceof Array,
			'Initiated items array.'
		);

		toolbar.destroy();

		assert.equal(
			toolbar._items,
			null,
			'Destroyed items.'
		);

		assert.equal(
			$toolbar.data( 'toolbar' ),
			null,
			'Destroyed widget.'
		);

	} );


	QUnit.test( 'Fill and remove', function( assert ) {
		var $toolbar = $( '<span/>' ).toolbar(),
			toolbar = $toolbar.data( 'toolbar' ),
			$label = $( '<span/>' ).text( 'label text' ).toolbarlabel();

		toolbar.addElement( $label );

		assert.equal(
			toolbar.hasElement( $label ),
			true,
			'Added label to toolbar.'
		);

		assert.equal(
			toolbar.getLength(), 1,
			'Toolbar length is 1.'
		);

		toolbar.addElement( $label );

		assert.equal(
			toolbar.getLength(), 1,
			'Toolbar length remains 1 after trying to add the same label again.'
		);

		var $button = $( '<a/>' ).text( 'button text' ).toolbarbutton();

		toolbar.addElement( $button );

		assert.equal(
			toolbar.hasElement( $button ),
			true,
			'Added a button to toolbar.'
		);

		assert.equal(
			toolbar.getLength(), 2,
			'Toolbar length is 2.'
		);

		assert.equal(
			toolbar.getIndexOf( $button ),
			1,
			'Correctly polled index of button.'
		);

		assert.equal(
			toolbar.getElements().length,
			2,
			'Confirmed toolbar length.'
		);

		var $secondLabel = $( '<span/>' ).text( 'second label text' ).toolbarlabel();

		toolbar.addElement( $secondLabel, 1 );

		assert.equal(
			toolbar.hasElement( $secondLabel ),
			true,
			'Inserted a label as second element to toolbar.'
		);

		assert.equal(
			toolbar.getLength(), 3,
			'Toolbar length is 3.'
		);

		assert.equal(
			toolbar.getIndexOf( $secondLabel ),
			1,
			'Correctly polled index of label.'
		);

		assert.equal(
			toolbar.element.children().length,
			3,
			'Verified DOM: Toolbar element has 3 children.'
		);

		assert.equal(
			toolbar.removeElement( $secondLabel ),
			true,
			'Removed second label.'
		);

		assert.equal(
			toolbar.getLength(), 2,
			'Toolbar length is 2.'
		);

		assert.equal(
			toolbar.element.children().length,
			2,
			'Verified DOM: Toolbar element has 2 two children.'
		);

		assert.equal(
			toolbar.getIndexOf( $secondLabel ),
			-1,
			'Second label not referenced anymore.'
		);

		assert.equal(
			toolbar.hide(),
			true,
			'Hide toolbar.'
		);

		assert.equal(
			toolbar.show(),
			true,
			'Show toolbar.'
		);

	} );


	QUnit.test( 'Dis- and enabling', function( assert ) {
		var $toolbar = $( '<span/>' ).toolbar(),
			toolbar = $toolbar.data( 'toolbar' ),
			$label = $( '<span/>' ).text( 'label text' ).toolbarlabel( { stateChangeable: false } );

		toolbar.addElement( $label );

		assert.equal(
			toolbar.isStateChangeable(),
			false,
			'Toolbar state is not changeable (no elements that are changeable).'
		);

		var $button = $( '<a/>' ).text( 'button text' ).toolbarbutton();
		toolbar.addElement( $button );

		assert.equal(
			toolbar.isStateChangeable(),
			true,
			'Toolbar state is changeable after adding a button.'
		);

		assert.equal(
			toolbar.disable(),
			true,
			'Disabling toolbar.'
		);

		assert.equal(
			toolbar.isDisabled(),
			true,
			'Toolbar is disabled.'
		);

		$label.data( 'toolbarlabel' ).option( 'stateChangeable', true );

		assert.equal(
			toolbar.getState(),
			wb.utilities.ui.StatableObject.prototype.STATE.MIXED,
			'Mixed state after making label changeable.'
		);

		assert.equal(
			toolbar.isDisabled(),
			false,
			'Toolbar is not disabled (since element states are mixed).'
		);

		assert.equal(
			toolbar.isEnabled(),
			false,
			'Toolbar is not enabled (since element states are mixed).'
		);

		assert.equal(
			toolbar.enable(),
			true,
			'Enabling toolbar.'
		);

		assert.equal(
			toolbar.isEnabled(),
			true,
			'Toolbar is enabled.'
		);

		assert.equal(
			toolbar.disable(),
			true,
			'Disabling toolbar.'
		);

		assert.equal(
			toolbar.isDisabled(),
			true,
			'Toolbar is disabled.'
		);

		assert.equal(
			toolbar.isEnabled(),
			false,
			'Toolbar is not enabled.'
		);

	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
