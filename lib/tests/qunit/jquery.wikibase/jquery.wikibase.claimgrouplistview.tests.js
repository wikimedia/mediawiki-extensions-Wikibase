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
var createClaimgrouplistview = function( options, $node ) {
	options = $.extend( {
		entityChangersFactory: {
			getClaimsChanger: function() {
				return 'i am a claimschanger';
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
		valueViewBuilder: 'i am a valueview builder'
	}, options || {} );

	$node = $node || $( '<div/>' ).appendTo( 'body' );

	var $claimgrouplistview = $node
		.addClass( 'test_claimgrouplistview' )
		.claimgrouplistview( options );

	return $claimgrouplistview;
};

QUnit.module( 'jquery.wikibase.claimgrouplistview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_claimgrouplistview' ).each( function() {
			var $claimgrouplistview = $( this ),
				claimgrouplistview = $claimgrouplistview.data( 'claimgrouplistview' );

			if( claimgrouplistview ) {
				claimgrouplistview.destroy();
			}

			$claimgrouplistview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	var $claimgrouplistview = createClaimgrouplistview(),
		claimgrouplistview = $claimgrouplistview.data( 'claimgrouplistview' );

	assert.ok(
		claimgrouplistview !== 'undefined',
		'Created widget.'
	);

	claimgrouplistview.destroy();

	assert.ok(
		$claimgrouplistview.data( 'claimgrouplistview' ) === undefined,
		'Destroyed widget.'
	);

	$claimgrouplistview = createClaimgrouplistview( {
		value: new wb.datamodel.ClaimGroupSet( [ new wb.datamodel.ClaimGroup( 'P1', new wb.datamodel.ClaimList() ) ] )
	} );
	claimgrouplistview = $claimgrouplistview.data( 'claimgrouplistview' );

	assert.ok(
		claimgrouplistview !== 'undefined',
		'Created widget with claimgroupset.'
	);
} );

QUnit.test( 'enterNewItem', function( assert ) {
	var $claimgrouplistview = createClaimgrouplistview(),
		claimgrouplistview = $claimgrouplistview.data( 'claimgrouplistview' );

	claimgrouplistview.enterNewItem();

	assert.equal( claimgrouplistview.listview().items().length, 1 );
} );

QUnit.test( 'enterNewItem & save', function( assert ) {
	var $claimgrouplistview = createClaimgrouplistview(),
		claimgrouplistview = $claimgrouplistview.data( 'claimgrouplistview' );

	claimgrouplistview.enterNewItem();

	var $claimlistview = claimgrouplistview.listview().items().eq( 0 );

	// Ugly hack to set the statementview value
	$claimlistview.find( ':wikibase-statementview' ).data( 'statementview' )._statement = new wb.datamodel.Statement(
		new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
	);

	assert.equal( $claimlistview.hasClass( 'wb-new' ), true );

	$claimlistview.data( 'claimlistview' )._trigger( 'afterstopediting', null, [ false ] );

	assert.equal( claimgrouplistview.listview().items().eq( 0 ).hasClass( 'wb-new' ), false );
} );

}( jQuery, wikibase, QUnit ) );
