/**
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */

( function( $, wb, QUnit ) {
'use strict';

/**
 * @param {Object} [options]
 * @param {jQuery} [$node]
 * @return {jQuery}
 */
var createStatementgrouplistview = function( options, $node ) {
	options = $.extend( {
		entityChangersFactory: {
			getClaimsChanger: function() {
				return 'i am a ClaimsChanger';
			},
			getReferencesChanger: function() {
				return null;
			}
		},
		entityStore: {
			get: function () {
				return $.Deferred().resolve().promise();
			}
		},
		api: 'i am an api',
		valueViewBuilder: 'i am a ValueViewBuilder'
	}, options || {} );

	$node = $node || $( '<div/>' ).appendTo( 'body' );

	return $node
		.addClass( 'test_statementgrouplistview' )
		.statementgrouplistview( options );
};

QUnit.module( 'jquery.wikibase.statementgrouplistview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_statementgrouplistview' ).each( function() {
			var $statementgrouplistview = $( this ),
				statementgrouplistview = $statementgrouplistview.data( 'statementgrouplistview' );

			if( statementgrouplistview ) {
				statementgrouplistview.destroy();
			}

			$statementgrouplistview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	var $statementgrouplistview = createStatementgrouplistview(),
		statementgrouplistview = $statementgrouplistview.data( 'statementgrouplistview' );

	assert.ok(
		statementgrouplistview instanceof $.wikibase.statementgrouplistview,
		'Created widget.'
	);

	statementgrouplistview.destroy();

	assert.ok(
		$statementgrouplistview.data( 'statementgrouplistview' ) === undefined,
		'Destroyed widget.'
	);

	$statementgrouplistview = createStatementgrouplistview( {
		value: new wb.datamodel.ClaimGroupSet( [
			new wb.datamodel.ClaimGroup( 'P1', new wb.datamodel.ClaimList() )
		] )
	} );
	statementgrouplistview = $statementgrouplistview.data( 'statementgrouplistview' );

	assert.ok(
		statementgrouplistview instanceof $.wikibase.statementgrouplistview,
		'Created widget with wb.datamodel.ClaimGroupSet instance.'
	);
} );

QUnit.test( 'enterNewItem', function( assert ) {
	var $statementgrouplistview = createStatementgrouplistview(),
		statementgrouplistview = $statementgrouplistview.data( 'statementgrouplistview' );

	assert.equal(
		statementgrouplistview.listview().items().length,
		0,
		'Plain widget has no items.'
	);

	statementgrouplistview.enterNewItem();

	assert.equal(
		statementgrouplistview.listview().items().length,
		1,
		'Increased number of items after calling enterNewItem().'
	);
} );

QUnit.test( 'enterNewItem & save', function( assert ) {
	var $statementgrouplistview = createStatementgrouplistview(),
		statementgrouplistview = $statementgrouplistview.data( 'statementgrouplistview' );

	statementgrouplistview.enterNewItem();

	var $claimlistview = statementgrouplistview.listview().items().eq( 0 );

	// Ugly hack to set the statementview value
	$claimlistview.find( ':wikibase-statementview' ).data( 'statementview' )._statement
		= new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		);

	assert.equal(
		$claimlistview.hasClass( 'wb-new' ),
		true,
		'Verified claimlistview widget being pending.'
	);

	$claimlistview.data( 'claimlistview' )._trigger( 'afterstopediting', null, [ false ] );

	assert.equal(
		statementgrouplistview.listview().items().eq( 0 ).hasClass( 'wb-new' ),
		false,
		'Verified new list item not being pending after saving.'
	);
} );

}( jQuery, wikibase, QUnit ) );
