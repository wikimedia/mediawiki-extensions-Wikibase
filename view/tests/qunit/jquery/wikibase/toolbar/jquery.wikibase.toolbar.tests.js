/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	QUnit.module( 'jquery.wikibase.toolbar', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_toolbar' ).each( function () {
				var $toolbar = $( this ),
					toolbar = $toolbar.data( 'toolbar' );

				if ( toolbar ) {
					toolbar.destroy();
				}

				$toolbar.remove();
			} );
		}
	} ) );

	/**
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	function createTestToolbar( options ) {
		return $( '<span>' )
			.addClass( 'test_toolbar' )
			.toolbar( options || {} );
	}

	QUnit.test( 'Create & destroy empty toolbar', function ( assert ) {
		var $toolbar = createTestToolbar(),
			toolbar = $toolbar.data( 'toolbar' );

		assert.true(
			toolbar instanceof $.wikibase.toolbar,
			'Instantiated widget.'
		);

		assert.strictEqual(
			toolbar.getContainer().get( 0 ),
			$toolbar.get( 0 ),
			'Verified toolbar node getting returned by getContainer().'
		);

		toolbar.destroy();

		assert.strictEqual(
			$toolbar.data( 'toolbar' ),
			undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'Create & destroy toolbar with initial content', function ( assert ) {
		var $container = $( '<span>' ),
			$button = $( '<span>' ).toolbarbutton( {
				$label: 'label'
			} ),
			$toolbar = createTestToolbar( {
				$content: $button,
				$container: $container
			} ),
			toolbar = $toolbar.data( 'toolbar' );

		assert.strictEqual(
			$container.contents().length,
			1,
			'Instantiated toolbar with one button.'
		);

		assert.strictEqual(
			$container.contents().first().data( 'toolbarbutton' ),
			$button.data( 'toolbarbutton' ),
			'Verified toolbar container containing the button.'
		);

		assert.strictEqual(
			toolbar.getContainer().get( 0 ),
			$container.get( 0 ),
			'Verified dedicated container node getting returned by getContainer().'
		);

		toolbar.destroy();

		assert.strictEqual(
			$container.contents().length,
			0,
			'Emptied toolbar container when destroying the toolbar.'
		);
	} );

	QUnit.test( 'Set content dynamically via option()', function ( assert ) {
		var $toolbar = createTestToolbar(),
			toolbar = $toolbar.data( 'toolbar' ),
			$button = $( '<span>' ).toolbarbutton( {
				$label: 'label'
			} );

		toolbar.option( '$content', $button );

		assert.strictEqual(
			$toolbar.contents().first().get( 0 ),
			$button.get( 0 ),
			'Added button.'
		);

		toolbar.option( '$content', $() );

		assert.strictEqual(
			$toolbar.contents().length,
			0,
			'Removed button.'
		);
	} );

}() );
