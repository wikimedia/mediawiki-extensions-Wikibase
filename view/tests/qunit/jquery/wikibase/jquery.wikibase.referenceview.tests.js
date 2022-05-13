/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	var listItemAdapter = wb.tests.getMockListItemAdapter(
		'snaklistview',
		function () {
			this.enterNewItem = function () {
				return $.Deferred().resolve( {
					data: function () {
						return { focus: function () {} };
					}
				} ).promise();
			};
			this.stopEditing = function () {};
			this.value = function () {
				return this.options.value;
			};
		}
	);

	/**
	 * Generates a referenceview widget suitable for testing.
	 *
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	function createReferenceview( options ) {
		options = $.extend( {
			getAdder: function () {
				return {
					destroy: function () {}
				};
			},
			getReferenceRemover: function () {
				return {
					destroy: function () {},
					disable: function () {},
					enable: function () {}
				};
			},
			getListItemAdapter: function () {
				return listItemAdapter;
			},
			removeCallback: function () {}
		}, options );

		return $( '<div>' )
			.addClass( 'test_referenceview' )
			.referenceview( options );
	}

	QUnit.module( 'jquery.wikibase.referenceview', window.QUnit.newMwEnvironment( {
		config: { wbRefTabsEnabled: false },
		afterEach: function () {
			$( '.test_referenceview' ).each( function ( i, node ) {
				var $node = $( node ),
					referenceview = $node.data( 'referenceview' );

				if ( referenceview ) {
					referenceview.destroy();
				}

				$node.remove();
			} );
		}
	} ) );

	QUnit.test( 'Initialize and destroy', function ( assert ) {
		var $node = createReferenceview(),
			referenceview = $node.data( 'referenceview' );

		assert.notStrictEqual(
			referenceview,
			undefined,
			'Initialized referenceview widget.'
		);

		assert.strictEqual(
			referenceview.value(),
			null,
			'Referenceview contains no reference.'
		);

		assert.strictEqual(
			referenceview.isInEditMode(),
			false,
			'Referenceview is not in edit mode.'
		);

		assert.notStrictEqual(
			referenceview.$listview.data( 'listview' ),
			undefined,
			'Initialized listview.'
		);

		referenceview.destroy();

		assert.strictEqual(
			referenceview.$listview,
			null,
			'Destroyed listview.'
		);
	} );

	QUnit.test( 'is initialized with a value', function ( assert ) {
		var $node = createReferenceview( {
				value: new datamodel.Reference( new datamodel.SnakList( [
					new datamodel.PropertyNoValueSnak( 'P1' )
				] ) )
			} ),
			referenceview = $node.data( 'referenceview' );

		assert.strictEqual(
			referenceview.value().getSnaks().length,
			1,
			'Referenceview contains a reference.'
		);
	} );

	QUnit.test( 'allows entering new item', function ( assert ) {
		var $node = createReferenceview(),
			referenceview = $node.data( 'referenceview' );

		referenceview.enterNewItem();

		assert.strictEqual(
			referenceview.isInEditMode(),
			true,
			'Referenceview is in edit mode.'
		);

		assert.strictEqual(
			referenceview.value(),
			null,
			'Referenceview contains no valid reference.'
		);

	} );

	QUnit.test( 'allows stopping editing', function ( assert ) {
		var $node = createReferenceview(),
			referenceview = $node.data( 'referenceview' );

		referenceview.enterNewItem();
		referenceview.stopEditing( true );

		assert.strictEqual(
			referenceview.isInEditMode(),
			false,
			'Referenceview is not in edit mode.'
		);

		assert.strictEqual(
			referenceview.value(),
			null,
			'Referenceview contains no reference.'
		);
	} );

}( wikibase ) );
