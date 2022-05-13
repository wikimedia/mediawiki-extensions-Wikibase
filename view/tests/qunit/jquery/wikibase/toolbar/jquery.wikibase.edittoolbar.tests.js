/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	QUnit.module( 'jquery.wikibase.edittoolbar', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_edittoolbar' ).each( function () {
				var $edittoolbar = $( this ),
					edittoolbar = $edittoolbar.data( 'edittoolbar' );

				if ( edittoolbar ) {
					edittoolbar.destroy();
				}

				$edittoolbar.remove();
			} );
		}
	} ) );

	/**
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	function createEdittoolbar( options ) {
		return $( '<span>' )
			.addClass( 'test_edittoolbar' )
			.edittoolbar( $.extend( {
				getHelpMessage: function () {
					return $.Deferred().resolve().promise();
				}
			}, options || {} ) );
	}

	QUnit.skip( 'Create & destroy', function ( assert ) {
		var $edittoolbar = createEdittoolbar(),
			edittoolbar = $edittoolbar.data( 'edittoolbar' );

		assert.ok(
			edittoolbar instanceof $.wikibase.edittoolbar,
			'Instantiated widget.'
		);

		edittoolbar.destroy();

		assert.ok(
			!$edittoolbar.data( 'edittoolbar' ),
			'Destroyed widget.'
		);

		$edittoolbar = createEdittoolbar( {
			onRemove: function () {}
		} );
		edittoolbar = $edittoolbar.data( 'edittoolbar' );

		assert.ok(
			edittoolbar instanceof $.wikibase.edittoolbar,
			'Instantiated widget with "onRemove" option set.'
		);

		edittoolbar.destroy();

		assert.ok(
			!$edittoolbar.data( 'edittoolbar' ),
			'Destroyed widget.'
		);
	} );

	QUnit.skip( 'Deferred button initialization', function ( assert ) {
		var $edittoolbar = createEdittoolbar(),
			edittoolbar = $edittoolbar.data( 'edittoolbar' ),
			deferredButtons = [ 'save', 'remove', 'cancel' ];

		assert.ok(
			edittoolbar._buttons.edit !== undefined,
			'Created "edit" button.'
		);

		for ( var i = 0; i < deferredButtons.length; i++ ) {
			assert.ok(
				edittoolbar._buttons[ deferredButtons[ i ] ] === undefined,
				'"' + deferredButtons[ i ] + '" not yet initialized.'
			);
		}

		var btnSave = edittoolbar.getButton( 'save' );

		assert.ok(
			btnSave instanceof $.wikibase.toolbarbutton,
			'Retrieved "save" button.'
		);

		assert.ok(
			edittoolbar._buttons.save !== undefined,
			'Cached "save" button.'
		);

		assert.ok(
			edittoolbar.getButton( 'save' ) === btnSave,
			'Returning cached button when retrieving button again.'
		);
	} );

	QUnit.skip( 'toEditMode(), toNonEditMode()', function ( assert ) {
		var $edittoolbar = createEdittoolbar(),
			edittoolbar = $edittoolbar.data( 'edittoolbar' );

		assert.strictEqual(
			$edittoolbar.find( ':wikibase-toolbarbutton' ).length,
			1,
			'Toolbar has 1 button.'
		);

		assert.strictEqual(
			$edittoolbar.find( ':wikibase-toolbarbutton' ).data( 'toolbarbutton' ),
			edittoolbar.getButton( 'edit' ),
			'Verified toolbar\'s button being the "edit" button.'
		);

		edittoolbar.toEditMode();

		assert.strictEqual(
			$edittoolbar.find( ':wikibase-toolbarbutton' ).length,
			2,
			'Toolbar contains 2 buttons after switching to edit mode.'
		);

		assert.strictEqual(
			$edittoolbar.find( ':wikibase-toolbarbutton' ).first().data( 'toolbarbutton' ),
			edittoolbar.getButton( 'save' ),
			'Verified toolbar\'s first button being the "save" button.'
		);

		assert.strictEqual(
			$edittoolbar.find( ':wikibase-toolbarbutton' ).last().data( 'toolbarbutton' ),
			edittoolbar.getButton( 'cancel' ),
			'Verified toolbar\'s last button being the "cancel" button.'
		);

		edittoolbar.toNonEditMode();

		assert.strictEqual(
			$edittoolbar.find( ':wikibase-toolbarbutton' ).length,
			1,
			'Toolbar has 1 button after switching back to non-edit mode.'
		);

		assert.strictEqual(
			$edittoolbar.find( ':wikibase-toolbarbutton' ).first().data( 'toolbarbutton' ),
			edittoolbar.getButton( 'edit' ),
			'Verified toolbar\'s button being the "edit" button.'
		);

		edittoolbar.option( 'onRemove', function () {} );

		edittoolbar.toEditMode();

		assert.strictEqual(
			$edittoolbar.find( ':wikibase-toolbarbutton' ).length,
			3,
			'Toolbar contains 3 buttons after switching to edit mode.'
		);

		assert.strictEqual(
			$edittoolbar.find( ':wikibase-toolbarbutton' ).eq( 1 ).data( 'toolbarbutton' ),
			edittoolbar.getButton( 'remove' ),
			'Verified toolbar\'s second button being the "remove" button.'
		);
	} );

}() );
