/**
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function () {
	'use strict';

	/**
	 * Factory for creating a preview widget suitable for testing.
	 *
	 * @param {Object} [options]
	 * @return {jQuery.ui.preview}
	 */
	var newTestPreview = function( options ) {
		if ( !options ) {
			options = $.extend( {}, options );
		}

		var $div = $( '<div/>' )
			.addClass( 'test_preview' )
			.appendTo( 'body' )
			.preview( options );

		return $div.data( 'preview' );
	};

	QUnit.module( 'jquery.ui.preview', {
		afterEach: function() {
			$( '.test_preview' ).each( function( i, node ) {
				if ( $( node ).data( 'preview' ) ) {
					$( node ).data( 'preview' ).destroy();
				}
				$( node ).remove();
			} );
		}
	} );

	QUnit.test( 'Initialization and destruction', function( assert ) {
		var preview = newTestPreview(),
			widgetBaseClass = preview.widgetBaseClass;

		assert.strictEqual(
			$( '.test_preview' ).data( 'preview' ),
			preview,
			'Initialized widget.'
		);

		preview.destroy();

		assert.strictEqual(
			$( '.test_preview' ).data( 'preview' ), undefined,
			'Destroyed widget.'
		);

		assert.strictEqual(
			$( '.' + widgetBaseClass + '-value' ).length,
			0,
			'Removed preview value node from DOM.'
		);
	} );

	QUnit.test( 'Update value', function( assert ) {
		var preview = newTestPreview();

		preview.update( 'test' );

		assert.strictEqual(
			preview.$value.children().length,
			0,
			'Preview has no child node.'
		);

		assert.strictEqual(
			preview.$value.text(),
			'test',
			'Updated preview.'
		);

		preview.showSpinner();

		assert.strictEqual(
			preview.$value.children().length,
			1,
			'Preview has only one child node.'
		);

		assert.ok(
			preview.$value.children( 'span' ).first().hasClass( 'small-spinner' ),
			'Applied spinner css class.'
		);

		preview.update( 'test2' );

		assert.strictEqual(
			preview.$value.text(),
			'test2',
			'Updated preview.'
		);

		assert.strictEqual(
			preview.$value.children().length,
			0,
			'Preview has no child node.'
		);

	} );

}() );
