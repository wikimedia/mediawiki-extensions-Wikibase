/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb, mw, QUnit ) {
	'use strict';

QUnit.module( 'jquery.wikibase.badgeselector', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_badgeselector' ).each( function() {
			var $node = $( this ),
				badgeselector = $node.data( 'badgeselector' );
			if( badgeselector ) {
				badgeselector.destroy();
			}
			$node.remove();
		} );
	}
} ) );

var entities =  {
	Q1: new wb.store.FetchedContent( {
		title: new mw.Title( 'Item:Q1' ),
		content: new wb.datamodel.Item( {
			id: 'Q1',
			type: 'item',
			labels: { en: { language: 'en', value: 'Q1-label' } }
		} )
	} ),
	Q2: new wb.store.FetchedContent( {
		title: new mw.Title( 'Item:Q2' ),
		content: new wb.datamodel.Item( {
			id: 'Q2',
			type: 'item',
			labels: { en: { language: 'en', value: 'Q2-label' } }
		} )
	} ),
	Q3: new wb.store.FetchedContent( {
		title: new mw.Title( 'Item:Q3' ),
		content: new wb.datamodel.Item( {
			id: 'Q3',
			type: 'item',
			labels: { en: { language: 'en', value: 'Q3-label' } }
		} )
	} )
};

var entityStore = {
	get: function( entityId ) {
		return $.Deferred().resolve( entities[entityId] );
	}
};


/**
 * @param {Object} [options]
 * @return {jQuery}
 */
function createBadgeselector( options ) {
	options = $.extend( {
		badges: {
			Q1: 'additionalCssClass-1',
			Q2: 'additionalCssClass-21 additionalCssClass22',
			Q3: 'additionalCssClass-3'
		},
		entityStore: entityStore,
		languageCode: 'en'
	}, options || {} );

	var $badgeselector = $( '<span/>' )
		.addClass( 'test_badgeselector' )
		.appendTo( 'body' )
		.badgeselector( options );

	var badgeselector = $badgeselector.data( 'badgeselector' );

	badgeselector._fetchItems = function() {
		return ( $.Deferred() ).resolve().promise();
	};

	return $badgeselector;
}

QUnit.test( 'Create & destroy', function( assert ) {
	var $badgeselector = createBadgeselector(),
		badgeselector = $badgeselector.data( 'badgeselector' );

	assert.ok(
		badgeselector !== undefined,
		'Instantiated widget.'
	);

	badgeselector.destroy();

	assert.ok(
		$badgeselector.data( 'badgeselector' ) === undefined,
		'Destroyed widget.'
	);
} );

QUnit.test( 'startEditing() & stopEditing()', 2, function( assert ) {
	var $badgeselector = createBadgeselector(),
		badgeselector = $badgeselector.data( 'badgeselector' );

	$badgeselector
	.on( 'badgeselectorafterstartediting', function( event ) {
		assert.ok(
			true,
			'Started edit mode.'
		);
	} )
	.on( 'badgeselectorafterstopediting', function( event, dropValue ) {
		assert.ok(
			true,
			'Stopped edit mode.'
		);
	} );

	badgeselector.startEditing();
	badgeselector.startEditing(); // should not trigger event
	badgeselector.stopEditing();
	badgeselector.stopEditing(); // should not trigger event
} );

QUnit.test( 'value()', function( assert ) {
	var $badgeselector = createBadgeselector(),
		badgeselector = $badgeselector.data( 'badgeselector' );

	assert.deepEqual(
		badgeselector.value(),
		[],
		'Returning empty value in non-edit mode.'
	);

	badgeselector.startEditing();

	assert.deepEqual(
		badgeselector.value(),
		[],
		'Returning empty value in edit mode regardless of placeholder badge.'
	);
} );

} )( jQuery, wikibase, mediaWiki, QUnit );
