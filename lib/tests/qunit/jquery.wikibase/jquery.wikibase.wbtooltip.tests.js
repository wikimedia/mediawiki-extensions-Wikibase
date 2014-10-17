/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb, QUnit ) {
	'use strict';

	/**
	 * Initializes a tooltip suitable for testing.
	 *
	 * @param {Object} options Tooltip widget options.
	 * @return {jQuery}
	 */
	function createTestTooltip( options ) {
		var $node = $( '<span>' )
			.addClass( 'test_tooltip' )
			.appendTo( 'body' )
			.wbtooltip( options );

		// Since Tipsy does not provide callbacks or events when doing fade operations, just disable
		// fading for testing:
		$node.data( 'wbtooltip' )._tipsy.options.fade = false;

		return $node;
	}

	QUnit.module( 'jquery.wikibase.wbtooltip', QUnit.newMwEnvironment( {
		teardown: function() {
			$( '.test_tooltip' ).each( function( i, node ) {
				var $node = $( node ),
					tooltip = $node.data( 'wbtooltip' );

				if( tooltip ) {
					tooltip.destroy();
				}

				$node.remove();
			} );
		}
	} ) );

	QUnit.test( 'Initialize and destroy.', function( assert ) {
		var $node = createTestTooltip( { content: 'Text' } ),
			tooltip = $node.data( 'wbtooltip' ),
			$tip;

		assert.ok(
			tooltip instanceof jQuery.wikibase.wbtooltip,
			'Initialized tooltip.'
		);

		tooltip.destroy();

		assert.ok(
			$node.data( 'wbtooltip' ) === undefined,
			'Destroyed tooltip.'
		);

		$node = createTestTooltip( { content: 'Text' } );
		tooltip = $node.data( 'wbtooltip' );

		tooltip.show();

		$tip = tooltip._tipsy.$tip;

		assert.ok(
			$tip.is( ':visible' ),
			'Tooltip balloon is visible after triggering show().'
		);
	} );

	QUnit.test( 'Show and hide basic tooltip.', function( assert ) {
		var $node = createTestTooltip( { content: 'Text' } ),
			tooltip = $node.data( 'wbtooltip' );

		assert.ok(
			tooltip instanceof $.wikibase.wbtooltip,
			'Initialized tooltip.'
		);

		assert.strictEqual(
			tooltip._tipsy.$tip,
			undefined,
			'Tooltip balloon is not yet initialized.'
		);

		tooltip.show();

		assert.ok(
			tooltip._tipsy.$tip.is( ':visible' ),
			'Tooltip balloon is visible after triggering show().'
		);

		tooltip.hide();

		assert.ok(
			!tooltip._tipsy.$tip.is( ':visible' ),
			'Tooltip balloon is hidden after triggering hide().'
		);
	} );

	QUnit.test( 'Permanent tooltip interaction.', function( assert ) {
		var $node = createTestTooltip( { content: 'Text', permanent: true } ),
			tooltip = $node.data( 'wbtooltip' );

		tooltip.show();

		tooltip._tipsy.$tip.trigger( 'click' );

		assert.ok(
			tooltip._tipsy.$tip.is( ':visible' ),
			'Tooltip balloon still visible after clicking on it.'
		);

		$( window ).trigger( 'resize' );

		assert.ok(
			tooltip._tipsy.$tip.is( ':visible' ),
			'Tooltip balloon still visible after triggering window resize event.'
		);

		$( window ).trigger( 'mousedown' );

		assert.ok(
			!tooltip._tipsy.$tip.is( ':visible' ),
			'Tooltip balloon hidden after triggering window click event.'
		);
	} );

	QUnit.test( 'Show and hide by triggering events.', function( assert ) {
		var $node = createTestTooltip( { content: 'Text' } ),
			tooltip = $node.data( 'wbtooltip' );

		assert.strictEqual(
			tooltip._tipsy.$tip,
			undefined,
			'Tooltip balloon not yet initialized.'
		);

		$node.trigger( 'mouseover' );

		assert.ok(
			tooltip._tipsy.$tip.is( ':visible' ),
			'Tooltip gets displayed on mouseover event.'
		);

		$node.trigger( 'mouseout' );

		assert.ok(
			!tooltip._tipsy.$tip.is( ':visible' ),
			'Tooltip gets hidden on mouseout event.'
		);

		$node.trigger( 'mouseover' );

		assert.ok(
			tooltip._tipsy.$tip.is( ':visible' ),
			'Tooltip gets displayed on mouseover event (2nd time).'
		);
	} );

	QUnit.test( 'Show and hide error tooltip.', function( assert ) {
		var error = new wb.RepoApiError( 'error-code', 'detailed message' ),
			$node = createTestTooltip( { content: error } ),
			tooltip = $node.data( 'wbtooltip' );

		tooltip.show();

		assert.strictEqual(
			tooltip._tipsy.$tip.find( '.wb-error' ).length,
			1,
			'Constructed error tooltip.'
		);
	} );

} )( jQuery, wikibase, QUnit );
