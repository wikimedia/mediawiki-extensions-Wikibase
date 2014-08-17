/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, $, QUnit ) {
	'use strict';

	QUnit.module( 'jQuery.wikibase.toolbar', QUnit.newMwEnvironment() );


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


	QUnit.test( 'disable(), enable()', function( assert ) {
		var $toolbar = $( '<span/>' ).toolbar(),
			toolbar = $toolbar.data( 'toolbar' ),
			$label = $( '<span/>' ).text( 'label text' ).toolbarlabel(),
			$button = $( '<a/>' ).text( 'button text' ).toolbarbutton();

		toolbar.addElement( $label );
		toolbar.addElement( $button );

		assert.ok(
			!toolbar.option( 'disabled' ),
			'Toolbar is enabled.'
		);

		toolbar.disable();

		assert.ok(
			toolbar.option( 'disabled' ),
			'Disabled toolbar.'
		);

		toolbar.enable();

		assert.ok(
			!toolbar.option( 'disabled' ),
			'Enabled toolbar.'
		);
	} );

}( wikibase, jQuery, QUnit ) );
