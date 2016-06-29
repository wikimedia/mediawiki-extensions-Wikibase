/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( $, wb, QUnit ) {
'use strict';

var statementgroupviewListItemAdapter = wb.tests.getMockListItemAdapter(
	'statementgroupview',
	function() {
		this.enterNewItem = function() {};
	}
);

/**
 * @param {Object} [options={}]
 * @param {jQuery} [$node]
 * @return {jQuery}
 */
var createStatementgrouplistview = function( options, $node ) {
	options = $.extend( {
		getAdder: function() {
			return {
				destroy: function() {}
			};
		},
		listItemAdapter: statementgroupviewListItemAdapter,
		value: new wb.datamodel.StatementGroupSet()
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

			if ( statementgrouplistview ) {
				statementgrouplistview.destroy();
			}

			$statementgrouplistview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.expect( 3 );
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
		value: new wb.datamodel.StatementGroupSet( [
			new wb.datamodel.StatementGroup( 'P1', new wb.datamodel.StatementList() )
		] )
	} );
	statementgrouplistview = $statementgrouplistview.data( 'statementgrouplistview' );

	assert.ok(
		statementgrouplistview instanceof $.wikibase.statementgrouplistview,
		'Created widget with filled wb.datamodel.StatementGroupSet instance.'
	);
} );

QUnit.test( 'enterNewItem', function( assert ) {
	assert.expect( 2 );
	var $statementgrouplistview = createStatementgrouplistview(),
		statementgrouplistview = $statementgrouplistview.data( 'statementgrouplistview' );

	assert.equal(
		statementgrouplistview.listview.items().length,
		0,
		'Plain widget has no items.'
	);

	statementgrouplistview.enterNewItem();

	assert.equal(
		statementgrouplistview.listview.items().length,
		1,
		'Increased number of items after calling enterNewItem().'
	);
} );

QUnit.test( 'enterNewItem & save', function( assert ) {
	assert.expect( 2 );
	var $statementgrouplistview = createStatementgrouplistview(),
		statementgrouplistview = $statementgrouplistview.data( 'statementgrouplistview' );

	statementgrouplistview.enterNewItem();

	var $statementgroupview = statementgrouplistview.listview.items().first();

	assert.ok(
		$statementgroupview.hasClass( 'wb-new' ),
		'Verified statementgroupview widget being pending.'
	);

	$statementgroupview.wrap( '<div/>' );
	$statementgroupview.trigger( 'afterstopediting', [false] );

	assert.ok(
		!statementgrouplistview.listview.items().first().hasClass( 'wb-new' ),
		'Verified new statementgroupview not being pending after saving.'
	);
} );

}( jQuery, wikibase, QUnit ) );
