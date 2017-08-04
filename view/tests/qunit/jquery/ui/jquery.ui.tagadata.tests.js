/**
 * @license GPL-2.0+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( $, QUnit ) {
	'use strict';

	/**
	 * @return {jQuery}
	 */
	function createTagadata() {
		return $( '<ul/>' )
			.addClass( 'test_tagadata' )
			.append( $( '<li/>' ).text( 'A' ) )
			.append( $( '<li/>' ).text( 'B' ) )
			.append( $( '<li/>' ).text( 'C' ) )
			.tagadata()
			.appendTo( 'body' );
	}

	QUnit.module( 'jquery.ui.tagadata', QUnit.newMwEnvironment( {
		teardown: function () {
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
		assert.expect( 2 );
		var $tagadata = createTagadata(),
			tagadata = $tagadata.data( 'tagadata' );

		assert.ok(
			tagadata !== undefined,
			'Initialized widget.'
		);

		tagadata.destroy();

		assert.ok(
			$tagadata.data( 'tagadata' ) === undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'Widget interaction', function ( assert ) {
		assert.expect( 6 );
		var $tagadata = createTagadata(),
			tagadata = $tagadata.data( 'tagadata' );

		assert.equal(
			tagadata.getTags().length,
			3,
			'Three tags attached.'
		);

		assert.equal(
			tagadata.getTagLabel( tagadata.getTags().first() ),
			'A',
			'Validated label.'
		);

		tagadata.createTag( 'foo', 'foo-class' );

		assert.equal(
			tagadata.getTags().length,
			4,
			'Attached new tag.'
		);

		assert.equal(
			tagadata.getTagLabel( tagadata.getTags().last() ),
			'foo',
			'Validated new tag\'s label.'
		);

		assert.ok(
			tagadata.getTag( 'foo' ).hasClass( 'foo-class' ),
			'Validated new tag\'s custom CSS class.'
		);

		assert.ok(
			tagadata.createTag( 'foo' ).stop().hasClass( 'foo-class' )
				&& tagadata.getTags().length === 4,
			'Creating tag which already exists returns existing tag instead of creating a new one.'
		);
	} );

	QUnit.test( 'removeTag()', function ( assert ) {
		var $tagadata = createTagadata(),
			tagadata = $tagadata.data( 'tagadata' ),
			done = assert.async( 2 );

		$tagadata.one( 'tagadatatagremoved', function () {
			assert.ok(
				true,
				'Removed tag.'
			);

			done();
		} );

		tagadata.removeTag( tagadata.getTag( 'B' ) );

		assert.strictEqual(
			tagadata.removeTag( $( '<span/>' ) ),
			false,
			'Trying to remove non-existent tag returns "false".'
		);

		done();
	} );

	QUnit.test( 'removeAll()', function ( assert ) {
		var $tagadata = createTagadata(),
			tagadata = $tagadata.data( 'tagadata' ),
			done = assert.async(),
			i = tagadata.getTags().length;

		$tagadata.on( 'tagadatatagremoved', function () {
			if ( --i === 0 ) {
				assert.ok(
					true,
					'Removed all tags.'
				);
				done();
			}
		} );

		tagadata.removeAll();
	} );

	QUnit.test( 'disable(), enable()', function ( assert ) {
		assert.expect( 2 );
		var $tagadata = createTagadata(),
			tagadata = $tagadata.data( 'tagadata' );

		tagadata.disable();

		assert.ok(
			tagadata.option( 'disabled' ),
			'Disabled widget.'
		);

		tagadata.enable();

		assert.ok(
			!tagadata.option( 'disabled' ),
			'Enabled widget.'
		);
	} );

	QUnit.test( 'hasConflict()', function ( assert ) {
		assert.expect( 5 );
		var $tagadata = createTagadata(),
			tagadata = $tagadata.data( 'tagadata' );

		assert.ok(
			!tagadata.hasConflict(),
			'Empty widget does not have a conflict.'
		);

		tagadata.createTag( 'foo' );

		assert.ok(
			!tagadata.hasConflict(),
			'Widget containing a single tag does not have a conflict.'
		);

		var $tag = tagadata.createTag( 'bar' );

		assert.ok(
			!tagadata.hasConflict(),
			'Widget containing different tags does not have a conflict.'
		);

		$tag.find( 'input' ).val( 'foo' );

		assert.ok(
			tagadata.hasConflict(),
			'Detected conflict.'
		);

		tagadata.removeTag( $tag );

		assert.ok(
			!tagadata.hasConflict(),
			'Resolved conflict after removing conflicting tag.'
		);
	} );

}( jQuery, QUnit ) );
