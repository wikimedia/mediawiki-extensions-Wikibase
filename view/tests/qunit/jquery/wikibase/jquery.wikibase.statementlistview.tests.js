/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' ),
		statementviewListItemAdapter = wb.tests.getMockListItemAdapter(
			'statementview',
			function () {
				var _value = this.options.value;
				this.startEditing = function () {};
				this.value = function ( newValue ) {
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
	var createStatementlistview = function ( options, $node ) {
		options = $.extend( {
			getAdder: function () {
				return {
					destroy: function () {}
				};
			},
			getListItemAdapter: function () {
				return statementviewListItemAdapter;
			},
			value: new datamodel.StatementList()
		}, options || {} );

		$node = $node || $( '<div>' ).appendTo( document.body );

		return $node
			.addClass( 'test_statementlistview' )
			.statementlistview( options );
	};

	QUnit.module( 'jquery.wikibase.statementlistview', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_statementlistview' ).each( function () {
				var $statementlistview = $( this ),
					statementlistview = $statementlistview.data( 'statementlistview' );

				if ( statementlistview ) {
					statementlistview.destroy();
				}

				$statementlistview.remove();
			} );
		}
	} ) );

	QUnit.test( 'Create & destroy', function ( assert ) {
		var $statementlistview = createStatementlistview(),
			statementlistview = $statementlistview.data( 'statementlistview' );

		assert.true(
			statementlistview instanceof $.wikibase.statementlistview,
			'Created widget.'
		);

		statementlistview.destroy();

		assert.strictEqual(
			$statementlistview.data( 'statementlistview' ),
			undefined,
			'Destroyed widget.'
		);

		$statementlistview = createStatementlistview( {
			value: new datamodel.StatementList( [
				new datamodel.Statement( new datamodel.Claim(
					new datamodel.PropertyNoValueSnak( 'P1' )
				) )
			] )
		} );
		statementlistview = $statementlistview.data( 'statementlistview' );

		assert.true(
			statementlistview instanceof $.wikibase.statementlistview,
			'Created widget with filled datamodel.StatementList instance.'
		);
	} );

	QUnit.test( 'value()', function ( assert ) {
		var statementList1 = new datamodel.StatementList( [ new datamodel.Statement(
				new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P1' ) )
			) ] ),
			statementList2 = new datamodel.StatementList( [ new datamodel.Statement(
				new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P2' ) )
			) ] ),
			$statementlistview = createStatementlistview( {
				value: statementList1
			} ),
			statementlistview = $statementlistview.data( 'statementlistview' );

		assert.true(
			statementlistview.value().equals( statementList1 ),
			'Retrieved value.'
		);

		statementlistview.value( statementList2 );

		assert.true(
			statementlistview.value().equals( statementList2 ),
			'Retrieved value after setting a new value.'
		);

		var statementlistviewListview = statementlistview.$listview.data( 'listview' ),
			statementlistviewListviewLia = statementlistviewListview.listItemAdapter(),
			$statementview = statementlistviewListview.items().first(),
			statementview = statementlistviewListviewLia.liInstance( $statementview ),
			statement = new datamodel.Statement(
				new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P3' ) )
			);

		statementview.value = function () {
			return statement;
		};

		assert.true(
			statementlistview.value().equals( new datamodel.StatementList( [ statement ] ) ),
			'Retrieved current value after setting a new value on the statementview encapsulated by '
				+ 'the statementlistview.'
		);

		assert.true(
			statementlistview.option( 'value' ).equals( statementList2 ),
			'Retrieved value still persisting via option().'
		);
	} );

	QUnit.test( 'enterNewItem', function ( assert ) {
		var $statementlistview = createStatementlistview(),
			statementlistview = $statementlistview.data( 'statementlistview' );

		assert.strictEqual(
			statementlistview.$listview.data( 'listview' ).items().length,
			0,
			'Plain widget has no items.'
		);

		statementlistview.enterNewItem();

		assert.strictEqual(
			statementlistview.$listview.data( 'listview' ).items().length,
			1,
			'Increased number of items after calling enterNewItem().'
		);
	} );

}( wikibase ) );
