/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	var statementgroupviewListItemAdapter = wb.tests.getMockListItemAdapter(
		'statementgroupview',
		function () {
			this.enterNewItem = function () {};
		}
	);

	/**
	 * @param {Object} [options={}]
	 * @param {jQuery} [$node]
	 * @return {jQuery}
	 */
	var createStatementgrouplistview = function ( options, $node ) {
		options = $.extend( {
			getAdder: function () {
				return {
					destroy: function () {}
				};
			},
			listItemAdapter: statementgroupviewListItemAdapter,
			value: new datamodel.StatementGroupSet()
		}, options || {} );

		$node = $node || $( '<div>' ).appendTo( document.body );

		return $node
			.addClass( 'test_statementgrouplistview' )
			.statementgrouplistview( options );
	};

	QUnit.module( 'jquery.wikibase.statementgrouplistview', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_statementgrouplistview' ).each( function () {
				var $statementgrouplistview = $( this ),
					statementgrouplistview = $statementgrouplistview.data( 'statementgrouplistview' );

				if ( statementgrouplistview ) {
					statementgrouplistview.destroy();
				}

				$statementgrouplistview.remove();
			} );
		}
	} ) );

	QUnit.test( 'Create & destroy', function ( assert ) {
		var $statementgrouplistview = createStatementgrouplistview(),
			statementgrouplistview = $statementgrouplistview.data( 'statementgrouplistview' );

		assert.true(
			statementgrouplistview instanceof $.wikibase.statementgrouplistview,
			'Created widget.'
		);

		statementgrouplistview.destroy();

		assert.strictEqual(
			$statementgrouplistview.data( 'statementgrouplistview' ),
			undefined,
			'Destroyed widget.'
		);

		$statementgrouplistview = createStatementgrouplistview( {
			value: new datamodel.StatementGroupSet( [
				new datamodel.StatementGroup( 'P1', new datamodel.StatementList() )
			] )
		} );
		statementgrouplistview = $statementgrouplistview.data( 'statementgrouplistview' );

		assert.true(
			statementgrouplistview instanceof $.wikibase.statementgrouplistview,
			'Created widget with filled datamodel.StatementGroupSet instance.'
		);
	} );

	QUnit.test( 'enterNewItem', function ( assert ) {
		var $statementgrouplistview = createStatementgrouplistview(),
			statementgrouplistview = $statementgrouplistview.data( 'statementgrouplistview' );

		assert.strictEqual(
			statementgrouplistview.listview.items().length,
			0,
			'Plain widget has no items.'
		);

		statementgrouplistview.enterNewItem();

		assert.strictEqual(
			statementgrouplistview.listview.items().length,
			1,
			'Increased number of items after calling enterNewItem().'
		);
	} );

	QUnit.test( 'enterNewItem & save', function ( assert ) {
		var $statementgrouplistview = createStatementgrouplistview(),
			statementgrouplistview = $statementgrouplistview.data( 'statementgrouplistview' );

		statementgrouplistview.enterNewItem();

		var $statementgroupview = statementgrouplistview.listview.items().first();

		assert.true(
			$statementgroupview.hasClass( 'wb-new' ),
			'Verified statementgroupview widget being pending.'
		);

		$statementgroupview.wrap( '<div/>' );
		$statementgroupview.trigger( 'afterstopediting', [ false ] );

		assert.false(
			statementgrouplistview.listview.items().first().hasClass( 'wb-new' ),
			'Verified new statementgroupview not being pending after saving.'
		);
	} );

}( wikibase ) );
