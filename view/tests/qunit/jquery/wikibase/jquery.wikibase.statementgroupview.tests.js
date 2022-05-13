/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	/**
	 * @param {Object} [options={}]
	 * @param {jQuery} [$node]
	 * @return {jQuery}
	 */
	var createStatementgroupview = function ( options, $node ) {
		options = $.extend( {
			entityIdHtmlFormatter: {
				format: function ( entityId ) {
					return $.Deferred().resolve( 'Link to entity' ).promise();
				}
			},
			buildStatementListView: function ( value ) {
				return {
					_value: value,
					destroy: function () {}, // FIXME: There should be a test spying on this
					element: { off: function () {} }, // FIXME: There should be a test spying on this
					value: function () {
						if ( arguments.length ) {
							this._value = arguments[ 0 ];
						}
						return this._value;
					}
				};
			}
		}, options || {} );

		$node = $node || $( '<div>' ).appendTo( document.body );

		return $node
			.addClass( 'test_statementgroupview' )
			.statementgroupview( options );
	};

	QUnit.module( 'jquery.wikibase.statementgroupview', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_statementgroupview' ).each( function () {
				var $statementgroupview = $( this ),
					statementgroupview = $statementgroupview.data( 'statementgroupview' );

				if ( statementgroupview ) {
					statementgroupview.destroy();
				}

				$statementgroupview.remove();
			} );
		}
	} ) );

	QUnit.test( 'Create & destroy', function ( assert ) {
		var $statementgroupview = createStatementgroupview(),
			statementgroupview = $statementgroupview.data( 'statementgroupview' );

		assert.true(
			statementgroupview instanceof $.wikibase.statementgroupview,
			'Created widget.'
		);

		statementgroupview.destroy();

		assert.strictEqual(
			$statementgroupview.data( 'statementgroupview' ),
			undefined,
			'Destroyed widget.'
		);

		$statementgroupview = createStatementgroupview( {
			value: new datamodel.StatementGroup( 'P1', new datamodel.StatementList( [
				new datamodel.Statement(
					new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P1' ) )
				)
			] ) )
		} );
		statementgroupview = $statementgroupview.data( 'statementgroupview' );

		assert.true(
			statementgroupview instanceof $.wikibase.statementgroupview,
			'Created widget with filled datamodel.StatementGroup instance.'
		);
	} );

	QUnit.test( 'value()', function ( assert ) {
		var statementGroup1 = new datamodel.StatementGroup( 'P1', new datamodel.StatementList( [
				new datamodel.Statement(
					new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P1' ) )
				)
			] ) ),
			statementGroup2 = new datamodel.StatementGroup( 'P2', new datamodel.StatementList( [
				new datamodel.Statement(
					new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P2' ) )
				)
			] ) ),
			$statementgroupview = createStatementgroupview( {
				value: statementGroup1
			} ),
			statementgroupview = $statementgroupview.data( 'statementgroupview' );

		assert.true(
			statementgroupview.value().equals( statementGroup1 ),
			'Retrieved value.'
		);

		statementgroupview.value( statementGroup2 );

		assert.true(
			statementgroupview.value().equals( statementGroup2 ),
			'Retrieved value after setting a new value.'
		);

		var statementlistview = statementgroupview.statementlistview,
			statementList1 = new datamodel.StatementList( [
				new datamodel.Statement(
					new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P2' ) )
				),
				new datamodel.Statement(
					new datamodel.Claim( new datamodel.PropertySomeValueSnak( 'P2' ) )
				)
			] ),
			statementList2 = new datamodel.StatementList( [
				new datamodel.Statement(
					new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P3' ) )
				)
			] ),
			statementList3 = new datamodel.StatementList( [
				new datamodel.Statement(
					new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P1' ) )
				),
				new datamodel.Statement(
					new datamodel.Claim( new datamodel.PropertyNoValueSnak( 'P2' ) )
				)
			] );

		statementlistview.value( statementList1 );

		assert.true(
			statementgroupview.value().equals(
				new datamodel.StatementGroup( 'P2', statementList1 )
			),
			'Retrieved current value after setting a new value to the statementlistview encapsulated '
				+ 'by the statementgroupview.'
		);

		assert.true(
			statementgroupview.option( 'value' ).equals( statementGroup2 ),
			'Retrieved value still persisting via option().'
		);

		statementlistview.value( statementList2 );

		assert.true(
			statementgroupview.value().equals(
				new datamodel.StatementGroup( 'P3', statementList2 )
			),
			'Retrieved current value after setting a new value featuring another Property to the '
				+ 'statementlistview encapsulated by the statementgroupview.'
		);

		statementlistview.value( statementList3 );

		assert.throws(
			function () {
				statementgroupview.value();
			},
			'Property of Statements in statementlistview differ resulting in not being able to '
				+ 'instantiate a StatementGroup.'
		);
	} );

	QUnit.test( 'Given a value, sets html id attribute on creation', function ( assert ) {
		var $statementgroupview = createStatementgroupview( {
				value: new datamodel.StatementGroup( 'P1' )
			} ),
			statementgroupview = $statementgroupview.data( 'statementgroupview' );

		assert.strictEqual(
			statementgroupview.element.attr( 'id' ),
			'P1'
		);
	} );

	QUnit.test( 'Given a value, sets property id data attribute on creation', function ( assert ) {
		var $statementgroupview = createStatementgroupview( {
				value: new datamodel.StatementGroup( 'P1' )
			} ),
			statementgroupview = $statementgroupview.data( 'statementgroupview' );

		assert.strictEqual(
			statementgroupview.element.data( 'property-id' ),
			'P1'
		);
	} );

	QUnit.test( 'Given a value and a prefix, sets prefixed html id attribute on creation', function ( assert ) {
		var $statementgroupview = createStatementgroupview( {
				value: new datamodel.StatementGroup( 'P1' ),
				htmlIdPrefix: 'X1-Y2'
			} ),
			statementgroupview = $statementgroupview.data( 'statementgroupview' );

		assert.strictEqual(
			statementgroupview.element.attr( 'id' ),
			'X1-Y2-P1'
		);
	} );

}() );
