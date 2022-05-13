/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	/**
	 * @return {jQuery}
	 */
	function createTagadata() {
		return $( '<ul>' )
			.addClass( 'test_tagadata' )
			.append( $( '<li>' ).text( 'A' ) )
			.append( $( '<li>' ).text( 'B' ) )
			.append( $( '<li>' ).text( 'C' ) )
			.tagadata()
			.appendTo( document.body );
	}

	QUnit.module( 'jquery.ui.tagadata', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_tagadata' ).each( function () {
				var $tagadata = $( this ),
					tagadata = $( this ).data( 'tagadata' );

				if ( tagadata ) {
					tagadata.destroy();
				}

				$tagadata.remove();
			} );
		}
	} ) );

	QUnit.test( 'Create & destroy', function ( assert ) {
		var $tagadata = createTagadata(),
			tagadata = $tagadata.data( 'tagadata' );

		assert.notStrictEqual(
			tagadata,
			undefined,
			'Initialized widget.'
		);

		tagadata.destroy();

		assert.strictEqual(
			$tagadata.data( 'tagadata' ),
			undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'Widget interaction', function ( assert ) {
		var $tagadata = createTagadata(),
			tagadata = $tagadata.data( 'tagadata' );

		assert.strictEqual(
			tagadata.getTags().length,
			3,
			'Three tags attached.'
		);

		assert.strictEqual(
			tagadata.getTagLabel( tagadata.getTags().first() ),
			'A',
			'Validated label.'
		);

		tagadata.createTag( 'foo', 'foo-class' );

		assert.strictEqual(
			tagadata.getTags().length,
			4,
			'Attached new tag.'
		);

		assert.strictEqual(
			tagadata.getTagLabel( tagadata.getTags().last() ),
			'foo',
			'Validated new tag\'s label.'
		);

		assert.strictEqual(
			tagadata.getTag( 'foo' ).hasClass( 'foo-class' ),
			true,
			'Validated new tag\'s custom CSS class.'
		);

		assert.strictEqual(
			tagadata.createTag( 'foo' ).stop().hasClass( 'foo-class' )
				&& tagadata.getTags().length === 4,
			true,
			'Creating tag which already exists returns existing tag instead of creating a new one.'
		);
	} );

	QUnit.test( 'removeTag()', function ( assert ) {
		var $tagadata = createTagadata(),
			tagadata = $tagadata.data( 'tagadata' ),
			done = assert.async( 2 );

		$tagadata.one( 'tagadatatagremoved', function () {
			assert.true(
				true,
				'Removed tag.'
			);

			done();
		} );

		tagadata.removeTag( tagadata.getTag( 'B' ) );

		assert.strictEqual(
			tagadata.removeTag( $( '<span>' ) ),
			false,
			'Trying to remove non-existent tag returns "false".'
		);

		done();
	} );

	QUnit.test( 'disable(), enable()', function ( assert ) {
		var $tagadata = createTagadata(),
			tagadata = $tagadata.data( 'tagadata' );

		tagadata.disable();

		assert.strictEqual(
			tagadata.option( 'disabled' ),
			true,
			'Disabled widget.'
		);

		tagadata.enable();

		assert.strictEqual(
			tagadata.option( 'disabled' ),
			false,
			'Enabled widget.'
		);
	} );

	QUnit.test( 'hasConflict()', function ( assert ) {
		var $tagadata = createTagadata(),
			tagadata = $tagadata.data( 'tagadata' );

		assert.strictEqual(
			tagadata.hasConflict(),
			false,
			'Empty widget does not have a conflict.'
		);

		tagadata.createTag( 'foo' );

		assert.strictEqual(
			tagadata.hasConflict(),
			false,
			'Widget containing a single tag does not have a conflict.'
		);

		var $tag = tagadata.createTag( 'bar' );

		assert.strictEqual(
			tagadata.hasConflict(),
			false,
			'Widget containing different tags does not have a conflict.'
		);

		$tag.find( 'input' ).val( 'foo' );

		assert.strictEqual(
			tagadata.hasConflict(),
			true,
			'Detected conflict.'
		);

		tagadata.removeTag( $tag );

		assert.strictEqual(
			tagadata.hasConflict(),
			false,
			'Resolved conflict after removing conflicting tag.'
		);
	} );

}() );
