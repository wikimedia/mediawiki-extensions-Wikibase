/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */

/* eslint-disable no-jquery/no-sizzle */

( function ( wb ) {
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
		afterEach: function () {
			$( '.test_tooltip' ).each( function ( i, node ) {
				var $node = $( node ),
					tooltip = $node.data( 'wbtooltip' );

				if ( tooltip ) {
					tooltip.destroy();
				}

				$node.remove();
			} );
		}
	} ) );

	QUnit.test( 'Initialize and destroy.', function ( assert ) {
		var $node = createTestTooltip( { content: 'Text' } ),
			tooltip = $node.data( 'wbtooltip' ),
			$tip;

		assert.true(
			tooltip instanceof $.wikibase.wbtooltip,
			'Initialized tooltip.'
		);

		tooltip.destroy();

		assert.strictEqual(
			$node.data( 'wbtooltip' ),
			undefined,
			'Destroyed tooltip.'
		);

		$node = createTestTooltip( { content: 'Text' } );
		tooltip = $node.data( 'wbtooltip' );

		tooltip.show();

		$tip = tooltip._tipsy.$tip;

		assert.strictEqual(
			$tip.is( ':visible' ),
			true,
			'Tooltip balloon is visible after triggering show().'
		);
	} );

	QUnit.test( 'Show and hide basic tooltip.', function ( assert ) {
		var $node = createTestTooltip( { content: 'Text' } ),
			tooltip = $node.data( 'wbtooltip' );

		assert.true(
			tooltip instanceof $.wikibase.wbtooltip,
			'Initialized tooltip.'
		);

		assert.strictEqual(
			tooltip._tipsy.$tip,
			undefined,
			'Tooltip balloon is not yet initialized.'
		);

		tooltip.show();

		assert.strictEqual(
			tooltip._tipsy.$tip.is( ':visible' ),
			true,
			'Tooltip balloon is visible after triggering show().'
		);

		tooltip.hide();

		assert.strictEqual(
			tooltip._tipsy.$tip.is( ':visible' ),
			false,
			'Tooltip balloon is hidden after triggering hide().'
		);
	} );

	QUnit.test( 'Permanent tooltip interaction.', function ( assert ) {
		var $node = createTestTooltip( { content: 'Text', permanent: true } ),
			tooltip = $node.data( 'wbtooltip' );

		tooltip.show();

		tooltip._tipsy.$tip.trigger( 'click' );

		assert.strictEqual(
			tooltip._tipsy.$tip.is( ':visible' ),
			true,
			'Tooltip balloon still visible after clicking on it.'
		);

		$( window ).trigger( 'resize' );

		assert.strictEqual(
			tooltip._tipsy.$tip.is( ':visible' ),
			true,
			'Tooltip balloon still visible after triggering window resize event.'
		);

		$( window ).trigger( 'mousedown' );

		assert.strictEqual(
			tooltip._tipsy.$tip.is( ':visible' ),
			false,
			'Tooltip balloon hidden after triggering window click event.'
		);
	} );

	QUnit.test( 'Show and hide by triggering events.', function ( assert ) {
		var $node = createTestTooltip( { content: 'Text' } ),
			tooltip = $node.data( 'wbtooltip' );

		assert.strictEqual(
			tooltip._tipsy.$tip,
			undefined,
			'Tooltip balloon not yet initialized.'
		);

		$node.trigger( 'mouseover' );

		assert.strictEqual(
			tooltip._tipsy.$tip.is( ':visible' ),
			true,
			'Tooltip gets displayed on mouseover event.'
		);

		$node.trigger( 'mouseout' );

		assert.strictEqual(
			tooltip._tipsy.$tip.is( ':visible' ),
			false,
			'Tooltip gets hidden on mouseout event.'
		);

		$node.trigger( 'mouseover' );

		assert.strictEqual(
			tooltip._tipsy.$tip.is( ':visible' ),
			true,
			'Tooltip gets displayed on mouseover event (2nd time).'
		);
	} );

	QUnit.test( 'Show and hide error tooltip.', function ( assert ) {
		var error = new wb.api.RepoApiError( 'error-code', 'detailed message' ),
			$node = createTestTooltip( { content: error } ),
			tooltip = $node.data( 'wbtooltip' );

		tooltip.show();

		assert.strictEqual(
			tooltip._tipsy.$tip.find( '.wb-error' ).length,
			1,
			'Constructed error tooltip.'
		);
	} );

}( wikibase ) );
