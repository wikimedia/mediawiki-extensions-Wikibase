/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( $, mw, wb ) {
	'use strict';

	function createClaimview( value ) {
		var options = { value: value || null };

		return mw.template('wb-claim', 'new', 'wb-last', '', '')
			.addClass( 'test_claimview' )
			.claimview( options );
	}

	QUnit.module( 'jquery.wikibase.claimview', window.QUnit.newWbEnvironment( {
	} ) );

	QUnit.test( 'Initialize and destroy', function( assert ) {
		var $node = createClaimview(),
			claimview = $node.data( 'claimview' );

		assert.ok(
			claimview !== undefined,
			'Initialized claimview widget.'
		);

		claimview.destroy();

		assert.ok(
			$node.data( 'listview' ) === undefined,
			'Destroyed listview.'
		);
	} );
} )( jQuery, mediaWiki, wikibase );
