/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	/**
	 * @param {Object} [options]
	 * @param {jQuery} [$node]
	 * @return {jQuery}
	 */
	var createItemview = function ( options, $node ) {
		options = $.extend( {
			value: new datamodel.Item( 'Q1' ),
			buildEntityTermsView: function () {},
			buildStatementGroupListView: function () {},
			buildSitelinkGroupListView: function () {}
		}, options || {} );

		$node = $node || $( '<div>' ).appendTo( document.body );

		var $itemview = $node
			.addClass( 'test_itemview' )
			.itemview( options );

		return $itemview;
	};

	QUnit.module( 'jquery.wikibase.itemview', QUnit.newMwEnvironment( {
		afterEach: function () {
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
		assert.throws(
			function () {
				createItemview( { value: null } );
			},
			'Throwing error when trying to initialize widget without a value.'
		);

		var $itemview = createItemview(),
			itemview = $itemview.data( 'itemview' );

		assert.true(
			itemview instanceof $.wikibase.itemview,
			'Created widget.'
		);

		itemview.destroy();

		assert.strictEqual(
			$itemview.data( 'itemview' ),
			undefined,
			'Destroyed widget.'
		);

	} );

}() );
