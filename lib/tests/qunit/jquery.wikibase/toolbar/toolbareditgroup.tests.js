/**
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, $, QUnit ) {
	'use strict';

	QUnit.module( 'jQuery.wikibase.toolbareditgroup', QUnit.newWbEnvironment() );

	QUnit.test( 'init check', function( assert ) {
		var $editGroup = $( '<span/>' ).toolbareditgroup(),
			editGroup = $editGroup.data( 'toolbareditgroup' );

		assert.ok(
			editGroup.innerGroup instanceof jQuery.wikibase.toolbar,
			'Initiated inner group.'
		);

		assert.ok(
			editGroup.$tooltipAnchor instanceof $,
			'Initiated tooltip.'
		);

		assert.equal(
			editGroup.$tooltipAnchor.data( 'toolbarlabel' ).option( 'stateChangeable' ),
			false,
			'Tooltip state is not changeable.'
		);

		assert.ok(
			editGroup.$btnEdit.data( 'toolbarbutton' ) instanceof $.wikibase.toolbarbutton,
			'Initiated edit button.'
		);

		assert.ok(
			editGroup.$btnCancel.data( 'toolbarbutton' ) instanceof $.wikibase.toolbarbutton,
			'Initiated cancel button.'
		);

		assert.ok(
			editGroup.$btnSave.data( 'toolbarbutton' ) instanceof $.wikibase.toolbarbutton,
			'Initiated save button.'
		);

		assert.ok(
			editGroup.$btnRemove.data( 'toolbarbutton' ) instanceof $.wikibase.toolbarbutton,
			'Initiated remove button.'
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
			editGroup.innerGroup.hasElement( editGroup.$btnEdit ),
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

		assert.equal(
			editGroup.$btnEdit,
			null,
			'Destroyed edit button.'
		);

		assert.equal(
			editGroup.$btnCancel,
			null,
			'Destroyed cancel button.'
		);

		assert.equal(
			editGroup.$btnSave,
			null,
			'Destroyed save button.'
		);

		assert.equal(
			editGroup.$btnRemove,
			null,
			'Destroyed remove button.'
		);

	} );

}( wikibase, jQuery, QUnit ) );
