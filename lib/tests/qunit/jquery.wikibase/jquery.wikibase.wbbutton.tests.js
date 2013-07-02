/**
 * @since 0.4
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki at snater.com >
 */

( function( mw, wb, $, QUnit ) {
	'use strict';

	/**
	 * Factory for creating a new $.wikibase.wbbutton instance.
	 *
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	var newTestButton = function( options ) {
		options = options || {};

		return mw.template( 'wikibase-wbbutton','Label', 'javascript:void(0);' )
			.addClass( 'test_button' )
			.wbbutton( options );
	};

	QUnit.module( 'jquery.wikibase.wbbutton', QUnit.newWbEnvironment( {
		teardown: function() {
			$( '.test_button' ).each( function( i, node ) {
				var $node = $( node );

				if( $node.data( 'wbbutton' ) ) {
					$node.data( 'wbbutton' ).destroy();
				}

				$node.remove();
			} );
		}
	} ) );

	QUnit.test( 'Initialisation.', function( assert ) {
		var $node = newTestButton(),
			button = $node.data( 'wbbutton' );

		assert.ok(
			button.getContent() === 'Label',
			'Button was initialised properly.'
		);
	} );

	QUnit.test( 'Action event.', function( assert ) {
		var $node = newTestButton(),
			button = $node.data( 'wbbutton' );

		$node.on( 'wbbuttonaction', function( event ) {
			$( event.target ).data( 'wbbutton' ).__test = true;

			assert.ok(
				true,
				'Triggered \'action\' event'
			);
		} );

		$node.trigger( 'click' );

		assert.ok(
			button.__test,
			'Verified event target.'
		);
	} );

	QUnit.test( 'Apply and remove focus.', function( assert ) {
		var $node = newTestButton(),
			button = $node.data( 'wbbutton' );

		// Attach button to body in order to be able to focus it:
		$( 'body' ).append( $node );

		assert.ok(
			!$node.is( ':focus' ),
			'Button is not focused.'
		);

		button.setFocus();

		assert.ok(
			$node.is( ':focus' ),
			'Focused button. (An error at this stage might also occur if you removed the focus ' +
				'from the browser window.)'
		);

		button.removeFocus();

		assert.ok(
			!$node.is( ':focus' ),
			'Removed focus from button.'
		);

		button.disable();

		assert.ok(
			button.isDisabled(),
			'Disabled button.'
		);

		button.setFocus();

		assert.ok(
			$node.is( ':focus' ),
			'Focused button.'
		);

		button.enable();

		assert.ok(
			button.isEnabled(),
			'Enabled button.'
		);

		assert.ok(
			$node.is( ':focus' ),
			'Button remains focused.'
		);
	} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
