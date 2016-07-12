/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb, QUnit ) {
'use strict';

var statementviewListItemAdapter = wb.tests.getMockListItemAdapter(
	'statementview',
	function() {
		var _value = this.options.value;
		this.startEditing = function() {};
		this.value = function( newValue ) {
			if ( arguments.length ) {
				_value = newValue;
			}
			return _value;
		};
	}
);

/**
 * @param {Object} [options={}]
 * @param {jQuery} [$node]
 * @return {jQuery}
 */
var createStatementlistview = function( options, $node ) {
	options = $.extend( {
		statementsChanger: 'I am a StatementsChanger',
		listItemAdapter: statementviewListItemAdapter,
		value: new wb.datamodel.StatementList()
	}, options || {} );

	$node = $node || $( '<div/>' ).appendTo( 'body' );

	return $node
		.addClass( 'test_statementlistview' )
		.statementlistview( options );
};

QUnit.module( 'jquery.wikibase.statementlistview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_statementlistview' ).each( function() {
			var $statementlistview = $( this ),
				statementlistview = $statementlistview.data( 'statementlistview' );

			if ( statementlistview ) {
				statementlistview.destroy();
			}

			$statementlistview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.expect( 3 );
	var $statementlistview = createStatementlistview(),
		statementlistview = $statementlistview.data( 'statementlistview' );

	assert.ok(
		statementlistview instanceof $.wikibase.statementlistview,
		'Created widget.'
	);

	statementlistview.destroy();

	assert.ok(
		$statementlistview.data( 'statementlistview' ) === undefined,
		'Destroyed widget.'
	);

	$statementlistview = createStatementlistview( {
		value: new wb.datamodel.StatementList( [
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		] )
	} );
	statementlistview = $statementlistview.data( 'statementlistview' );

	assert.ok(
		statementlistview instanceof $.wikibase.statementlistview,
		'Created widget with filled wb.datamodel.StatementList instance.'
	);
} );

QUnit.test( 'value()', function( assert ) {
	assert.expect( 4 );
	var statementList1 = new wb.datamodel.StatementList( [new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		)] ),
		statementList2 = new wb.datamodel.StatementList( [new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P2' ) )
		)] ),
		$statementlistview = createStatementlistview( {
			value: statementList1
		} ),
		statementlistview = $statementlistview.data( 'statementlistview' );

	assert.ok(
		statementlistview.value().equals( statementList1 ),
		'Retrieved value.'
	);

	statementlistview.value( statementList2 );

	assert.ok(
		statementlistview.value().equals( statementList2 ),
		'Retrieved value after setting a new value.'
	);

	var statementlistviewListview = statementlistview.$listview.data( 'listview' ),
		statementlistviewListviewLia = statementlistviewListview.listItemAdapter(),
		$statementview = statementlistviewListview.items().first(),
		statementview = statementlistviewListviewLia.liInstance( $statementview ),
		statement = new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P3' ) )
		);

	statementview.value = function() {
		return statement;
	};

	assert.ok(
		statementlistview.value().equals( new wb.datamodel.StatementList( [statement] ) ),
		'Retrieved current value after setting a new value on the statementview encapsulated by '
			+ 'the statementlistview.'
	);

	assert.ok(
		statementlistview.option( 'value' ).equals( statementList2 ),
		'Retrieved value still persisting via option().'
	);
} );

QUnit.test( 'isEmpty()', function( assert ) {
	assert.expect( 4 );
	var $statementlistview = createStatementlistview(),
		statementlistview = $statementlistview.data( 'statementlistview' );

	assert.ok(
		statementlistview.isEmpty(),
		'Verified isEmpty() returning TRUE when widget has been initialized with an empty '
			+ 'StatementList.'
	);

	$statementlistview = createStatementlistview( {
		value: new wb.datamodel.StatementList( [
			new wb.datamodel.Statement( new wb.datamodel.Claim(
					new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		] )
	} );
	statementlistview = $statementlistview.data( 'statementlistview' );

	assert.ok(
		!statementlistview.isEmpty(),
		'Verified isEmpty() returning FALSE when widget has been initialized with a filled '
			+ 'StatmentList.'
	);

	statementlistview.value( new wb.datamodel.StatementList() );

	assert.ok(
		statementlistview.isEmpty(),
		'Verified isEmpty() returning TRUE after setting an empty StatementList.'
	);

	statementlistview.value( new wb.datamodel.StatementList( [
		new wb.datamodel.Statement( new wb.datamodel.Claim(
			new wb.datamodel.PropertyNoValueSnak( 'P2' )
		) )
	] ) );

	assert.ok(
		!statementlistview.isEmpty(),
		'Verified isEmpty() returning FALSE after setting an filled StatementList.'
	);
} );

QUnit.test( 'enterNewItem', function( assert ) {
	assert.expect( 4 );
	var $statementlistview = createStatementlistview(),
		statementlistview = $statementlistview.data( 'statementlistview' );

	assert.equal(
		statementlistview.$listview.data( 'listview' ).items().length,
		0,
		'Plain widget has no items.'
	);

	statementlistview.enterNewItem();

	assert.equal(
		statementlistview.$listview.data( 'listview' ).items().length,
		1,
		'Increased number of items after calling enterNewItem().'
	);

	var statementlistviewListview = statementlistview.$listview.data( 'listview' ),
		statementlistviewListviewLia = statementlistviewListview.listItemAdapter(),
		$statementview = statementlistviewListview.items().first(),
		statementview = statementlistviewListviewLia.liInstance( $statementview );

	// Hack statementview to return a value for mocking "saving" action:
	statementview.value = function() {
		return new wb.datamodel.Statement(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		);
	};

	assert.ok(
		$statementview.hasClass( 'wb-new' ),
		'Verified statementview widget being pending.'
	);

	statementview._trigger( 'afterstopediting', null, [false] );

	assert.ok(
		!statementlistview.$listview.data( 'listview' ).items().eq( 0 ).hasClass( 'wb-new' ),
		'Verified new statementgroupview not being pending after saving.'
	);
} );

}( jQuery, wikibase, QUnit ) );
