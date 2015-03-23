/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb, QUnit ) {
'use strict';

/**
 * @param {Object} [options]
 * @param {jQuery} [$node]
 * @return {jQuery}
 */
var createItemview = function( options, $node ) {
	options = $.extend( {
		entityStore: new wb.store.EntityStore(),
		entityChangersFactory: {
			getAliasesChanger: function() { return 'I am an AliasesChanger'; },
			getDescriptionsChanger: function() { return 'I am a DescriptionsChanger'; },
			getLabelsChanger: function() { return 'I am a LabelsChanger'; },
			getSiteLinksChanger: function() { return 'I am a SiteLinksChanger'; }
		},
		api: 'I am an Api',
		valueViewBuilder: 'I am a valueview builder',
		dataTypeStore: 'I am a DataTypeStore',
		value: new wb.datamodel.Item( 'Q1' ),
		languages: 'en'
	}, options || {} );

	$node = $node || $( '<div/>' ).appendTo( 'body' );

	var $itemview = $node
		.addClass( 'test_itemview' )
		.itemview( options );

	$itemview.data( 'itemview' )._save = function() {
		return $.Deferred().resolve( {
			entity: {
				lastrevid: 'i am a revision id'
			}
		} ).promise();
	};

	return $itemview;
};

QUnit.module( 'jquery.wikibase.itemview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_itemview' ).each( function() {
			var $itemview = $( this ),
				itemview = $itemview.data( 'itemview' );

			if( itemview ) {
				itemview.destroy();
			}

			$itemview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.throws(
		function() {
			createItemview( { value: null } );
		},
		'Throwing error when trying to initialize widget without a value.'
	);

	assert.throws(
		function() {
			createItemview( { languages: null } );
		},
		'Throwing error when trying to initialize widget without a language.'
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

	$itemview = createItemview( { languages: ['ku'] } );
	itemview = $itemview.data( 'itemview' );

	assert.ok(
		itemview instanceof $.wikibase.itemview,
		'Created widget with a language.'
	);
} );

}( jQuery, wikibase, QUnit ) );
