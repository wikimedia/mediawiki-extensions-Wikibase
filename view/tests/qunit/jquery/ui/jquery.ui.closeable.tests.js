/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( $, QUnit ) {
	'use strict';

	/**
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	function createCloseable( options ) {
		return $( '<div/>' )
			.addClass( 'test_closeable' )
			.closeable( options || {} );
	}

	QUnit.module( 'jquery.ui.closeable', QUnit.newMwEnvironment( {
		teardown: function () {
			$( '.test_closeable' ).each( function () {
				var $closeable = $( this ),
					closeable = $( this ).data( 'closeable' );

				if ( closeable ) {
					closeable.destroy();
				}

				$closeable.remove();
			} );
		}
	} ) );

	QUnit.test( 'Create & destroy', function ( assert ) {
		assert.expect( 2 );
		var $closeable = createCloseable(),
			closeable = $closeable.data( 'closeable' );

		assert.ok(
			closeable instanceof $.ui.closeable,
			'Initialized widget.'
		);

		closeable.destroy();

		assert.ok(
			$closeable.data( 'closeable' ) === undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'Close when clicking "close" anchor', function ( assert ) {
		assert.expect( 2 );
		var $closeable = createCloseable( {
				$content: $( '<span>test</span>' )
			} ),
			closeable = $closeable.data( 'closeable' );

		assert.ok(
			closeable.option( '$content' ) instanceof $,
			'Instantiated widget with initial content.'
		);

		closeable.$close.trigger( 'click' );

		assert.strictEqual(
			closeable.option( '$content' ),
			null,
			'Removed content after clicking "close" anchor.'
		);
	} );

	QUnit.test( 'setContent()', function ( assert ) {
		assert.expect( 7 );
		var $closeable = createCloseable(),
			closeable = $closeable.data( 'closeable' ),
			$content = $( '<span>test</span>' );

		$closeable.on( 'closeableupdate', function () {
			assert.ok(
				true,
				'Triggered "update" event.'
			);
		} );

		assert.strictEqual(
			closeable.option( '$content' ),
			null,
			'Instantiated empty widget.'
		);

		closeable.setContent( $content, 'cssClass' );

		assert.equal(
			closeable.option( '$content' ).get( 0 ),
			$content.get( 0 ),
			'Set content.'
		);

		assert.equal(
			closeable.option( 'cssClass' ),
			'cssClass',
			'Set CSS class.'
		);

		closeable.setContent( null, null );

		assert.strictEqual(
			closeable.option( '$content' ),
			null,
			'Removed content.'
		);

		assert.equal(
			closeable.option( 'cssClass' ),
			'',
			'Removed CSS class.'
		);
	} );

}( jQuery, QUnit ) );
