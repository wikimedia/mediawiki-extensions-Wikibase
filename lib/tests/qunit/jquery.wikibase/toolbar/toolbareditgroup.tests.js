/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, QUnit ) {
	'use strict';

	QUnit.module( 'jQuery.wikibase.toolbareditgroup', QUnit.newMwEnvironment() );

	QUnit.test( 'Create & Destroy', function( assert ) {
		var $editGroup = $( '<span/>' ).toolbareditgroup(),
			editGroup = $editGroup.data( 'toolbareditgroup' );

		assert.ok(
			editGroup.innerGroup instanceof jQuery.wikibase.toolbar,
			'Created inner group.'
		);

		assert.ok(
			editGroup.$tooltipAnchor instanceof $,
			'Created tooltip.'
		);

		assert.equal(
			editGroup.$tooltipAnchor.data( 'toolbarlabel' ).option( 'stateChangeable' ),
			false,
			'Tooltip state is not changeable.'
		);

		assert.ok(
			editGroup.hasButton( 'edit' ) && editGroup.getButton( 'edit' ).data( 'toolbarbutton' )
				instanceof $.wikibase.toolbarbutton,
			'Created edit button.'
		);

		assert.ok(
			!editGroup.hasButton( 'btnCancel' ),
			'Cancel button not yet initialized.'
		);

		assert.ok(
			!editGroup.hasButton( 'btnSave' ),
			'Save button not yet initialized.'
		);

		assert.ok(
			!editGroup.hasButton( 'remove' ),
			'Remove button not yet initialized.'
		);

		assert.equal(
			editGroup.option( 'displayRemoveButton' ),
			false,
			'Remove button will not be displayed.'
		);

		assert.equal(
			editGroup.option( 'renderItemSeparators' ),
			false,
			'Item separators will not be displayed.'
		);

		assert.equal(
			editGroup.innerGroup.hasElement( editGroup.getButton( 'edit' ) ),
			true,
			'Edit button is in inner group.'
		);

		editGroup.destroy();

		assert.equal(
			editGroup.innerGroup,
			null,
			'Destroyed inner group.'
		);

		assert.equal(
			editGroup.$tooltipAnchor,
			null,
			'Destroyed tooltip.'
		);

		assert.ok(
			!editGroup.hasButton( 'edit' ),
			'Destroyed edit button.'
		);

		assert.ok(
			!editGroup.hasButton( 'cancel' ),
			'Cancel button does not exist.'
		);

		assert.ok(
			!editGroup.hasButton( 'save' ),
			'Save button does not exist.'
		);

		assert.ok(
			!editGroup.hasButton( 'remove' ),
			'Remove button does not exist.'
		);

	} );

	QUnit.test( 'toEditMode() & toNonEditMode()', function( assert ) {
		var $editGroup = $( '<span/>' ).toolbareditgroup( {
				displayRemoveButton: false
			} ),
			editGroup = $editGroup.data( 'toolbareditgroup' );

		editGroup.toEditMode();

		assert.ok(
			editGroup.hasButton( 'edit' ),
			'Created edit button.'
		);

		assert.ok(
			editGroup.hasButton( 'cancel' ),
			'Created cancel button.'
		);

		assert.ok(
			editGroup.hasButton( 'save' ),
			'Created save button.'
		);

		assert.ok(
			!editGroup.hasButton( 'remove' ),
			'Remove button is not initialized.'
		);

		assert.ok(
			!editGroup.innerGroup.hasElement( editGroup.getButton( 'edit' ) ),
			'Edit button is not visible.'
		);

		assert.ok(
			editGroup.innerGroup.hasElement( editGroup.getButton( 'save' ) ),
			'Save button is visible.'
		);

		assert.ok(
			editGroup.innerGroup.hasElement( editGroup.getButton( 'cancel' ) ),
			'Cancel button is visible.'
		);

		assert.ok(
			editGroup.hasElement( editGroup.$tooltipAnchor ),
			'Tooltip anchor is visible.'
		);

		editGroup.toNonEditMode();

		assert.ok(
			editGroup.innerGroup.hasElement( editGroup.getButton( 'edit' ) ),
			'Edit button is visible.'
		);

		assert.ok(
			!editGroup.innerGroup.hasElement( editGroup.getButton( 'save' ) ),
			'Save button is not visible.'
		);

		assert.ok(
			!editGroup.innerGroup.hasElement( editGroup.getButton( 'cancel' ) ),
			'Cancel button is not visible.'
		);

		assert.ok(
			!editGroup.hasElement( editGroup.$tooltipAnchor ),
			'Tooltip anchor is not visible.'
		);

	} );

	QUnit.test( 'Remove button handling on toEditMode() & toNonEditMode()', function( assert ) {
		var $editGroup = $( '<span/>' ).toolbareditgroup( {
				displayRemoveButton: true
			} ),
			editGroup = $editGroup.data( 'toolbareditgroup' );

		editGroup.toEditMode();

		assert.ok(
			editGroup.hasButton( 'remove' ),
			'Created remove button.'
		);

		assert.ok(
			editGroup.innerGroup.hasElement( editGroup.getButton( 'remove' ) ),
			'Remove button is visible.'
		);

		editGroup.toNonEditMode();

		assert.ok(
			!editGroup.innerGroup.hasElement( editGroup.getButton( 'remove' ) ),
			'Remove button is not visible.'
		);

	} );

	QUnit.test( 'getButton()', function( assert ) {
		var $editGroup = $( '<span/>' ).toolbareditgroup(),
			editGroup = $editGroup.data( 'toolbareditgroup' );

		assert.ok(
			!editGroup.hasButton( 'save' ),
			'"Save" button does not exist.'
		);

		var $btnSave = editGroup.getButton( 'save' );

		assert.ok(
			$btnSave instanceof jQuery && $btnSave.data( 'toolbarbutton' ) !== undefined,
			'Created "save" button.'
		);

		assert.strictEqual(
			editGroup.getButton( 'save' ),
			$btnSave,
			'Not re-instantiating button when re-retrieving it.'
		);

	} );

}( jQuery, QUnit ) );
