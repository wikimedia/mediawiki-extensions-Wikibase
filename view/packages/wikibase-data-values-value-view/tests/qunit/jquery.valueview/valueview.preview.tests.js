/**
 * @since 0.1
 * @ingroup ValueView
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function( $, QUnit ) {
	'use strict';

	/**
	 * Factory for creating a valueview preview widget suitable for testing.
	 *
	 * @param {Object} [options]
	 * @return {jQuery.valueview.preview}
	 */
	var newTestPreview = function( options ) {
		if( !options ) {
			options = $.extend( {}, options );
		}

		var $div = $( '<div/>' )
			.addClass( 'test_preview' )
			.appendTo( 'body' )
			.preview( options );

		return $div.data( 'preview' );
	};

	QUnit.module( 'jquery.valueview.preview', {
		teardown: function() {
			$( '.test_preview' ).each( function( i, node ) {
				if( $( node ).data( 'preview' ) ) {
					$( node ).data( 'preview' ).destroy();
				}
				$( node ).remove();
			} );
		}
	} );

	QUnit.test( 'Initialization and destruction', 3, function( assert ) {
		var preview = newTestPreview(),
			widgetBaseClass = preview.widgetBaseClass;

		assert.equal(
			$( '.test_preview' ).data( 'preview' ),
			preview,
			'Initialized widget.'
		);

		preview.destroy();

		assert.ok(
			$( '.test_preview' ).data( 'preview' ) === undefined,
			'Destroyed widget.'
		);

		assert.equal(
			$( '.' + widgetBaseClass + '-value' ).length,
			0,
			'Removed preview value node from DOM.'
		);
	} );

	QUnit.test( 'Update value', 1, function( assert ) {
		var preview = newTestPreview();

		preview.update( 'test' );

		assert.equal(
			preview.$value.text(),
			'test',
			'Updated preview.'
		);
	} );

}( jQuery, QUnit ) );
