/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( $, wb, QUnit ) {
	'use strict';

	/**
	 * @param {Object} [options]
	 * @param {jQuery} [$node]
	 * @return {jQuery}
	 */
	var createItemview = function ( options, $node ) {
		options = $.extend( {
			value: new wb.datamodel.Item( 'Q1' ),
			buildEntityTermsView: function () {},
			buildStatementGroupListView: function () {},
			buildSitelinkGroupListView: function () {}
		}, options || {} );

		$node = $node || $( '<div/>' ).appendTo( 'body' );

		var $itemview = $node
			.addClass( 'test_itemview' )
			.itemview( options );

		return $itemview;
	};

	QUnit.module( 'jquery.wikibase.itemview', QUnit.newMwEnvironment( {
		teardown: function () {
			$( '.test_itemview' ).each( function () {
				var $itemview = $( this ),
					itemview = $itemview.data( 'itemview' );

				if ( itemview ) {
					itemview.destroy();
				}

				$itemview.remove();
			} );
		}
	} ) );

	QUnit.test( 'Create & destroy', function ( assert ) {
		assert.expect( 3 );
		assert.throws(
			function () {
				createItemview( { value: null } );
			},
			'Throwing error when trying to initialize widget without a value.'
		);

		var $itemview = createItemview(),
			itemview = $itemview.data( 'itemview' );

		assert.ok(
			itemview instanceof $.wikibase.itemview,
			'Created widget.'
		);

		itemview.destroy();

		assert.ok(
			$itemview.data( 'itemview' ) === undefined,
			'Destroyed widget.'
		);

	} );

}( jQuery, wikibase, QUnit ) );
