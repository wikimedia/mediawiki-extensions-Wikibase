/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	/**
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	function createCloseable( options ) {
		return $( '<div>' )
			.addClass( 'test_closeable' )
			.closeable( options || {} );
	}

	QUnit.module( 'jquery.ui.closeable', QUnit.newMwEnvironment( {
		afterEach: function () {
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
		var $closeable = createCloseable(),
			closeable = $closeable.data( 'closeable' );

		assert.true(
			closeable instanceof $.ui.closeable,
			'Initialized widget.'
		);

		closeable.destroy();

		assert.strictEqual(
			$closeable.data( 'closeable' ),
			undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'Close when clicking "close" anchor', function ( assert ) {
		var $closeable = createCloseable( {
				$content: $( '<span>' ).text( 'test' )
			} ),
			closeable = $closeable.data( 'closeable' );

		assert.true(
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
		var $closeable = createCloseable(),
			closeable = $closeable.data( 'closeable' ),
			$content = $( '<span>' ).text( 'test' );

		$closeable.on( 'closeableupdate', function () {
			assert.true(
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

		assert.strictEqual(
			closeable.option( '$content' ).get( 0 ),
			$content.get( 0 ),
			'Set content.'
		);

		assert.strictEqual(
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

		assert.strictEqual(
			closeable.option( 'cssClass' ),
			'',
			'Removed CSS class.'
		);
	} );

}() );
