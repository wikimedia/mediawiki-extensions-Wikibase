/**
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */

( function( $, wb, QUnit ) {
'use strict';

/**
 * @param {Object} [options={}]
 * @param {jQuery} [$node]
 * @return {jQuery}
 */
var createStatementgrouplistview = function( options, $node ) {
	options = $.extend( {
		claimGuidGenerator: 'I am a ClaimGuidGenerator',
		entityIdHtmlFormatter: {
			format: function( entityId ) {
				return $.Deferred().resolve( entityId ).promise();
			}
		},
		entityIdPlainFormatter: {
			format: function( entityId ) {
				return $.Deferred().resolve( entityId ).promise();
			}
		},
		entityStore: 'I am an EntityStore',
		valueViewBuilder: 'I am a ValueViewBuilder',
		entityChangersFactory: {
			getClaimsChanger: function() {
				return 'I am a ClaimsChanger';
			},
			getReferencesChanger: function() {
				return 'I am a ReferencesChanger';
			}
		},
		dataTypeStore: 'I am a DataTypeStore',
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
	var $statementgrouplistview = createStatementgrouplistview(),
		statementgrouplistview = $statementgrouplistview.data( 'statementgrouplistview' ),
		statementgrouplistviewListview = statementgrouplistview.listview,
		statementgrouplistviewListviewLia = statementgrouplistviewListview.listItemAdapter();

	statementgrouplistview.enterNewItem();

	var $statementgroupview = statementgrouplistviewListview.items().first(),
		statementgroupview = statementgrouplistviewListviewLia.liInstance( $statementgroupview ),
		$statementlistview = statementgroupview.statementlistview.element;

	// Simulate having altered snakview's value:
	$statementlistview.find( ':wikibase-snakview' ).data( 'snakview' ).snak = function() {
		return new wb.datamodel.PropertyNoValueSnak( 'P1' );
	};

	assert.ok(
		$statementgroupview.hasClass( 'wb-new' ),
		'Verified statementgroupview widget being pending.'
	);

	$statementlistview.data( 'statementlistview' )._trigger( 'afterstopediting', null, [false] );

	assert.ok(
		!statementgrouplistview.listview.items().eq( 0 ).hasClass( 'wb-new' ),
		'Verified new statementgroupview not being pending after saving.'
	);
} );

}( jQuery, wikibase, QUnit ) );
