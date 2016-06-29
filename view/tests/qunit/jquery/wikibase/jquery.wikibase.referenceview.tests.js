/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( $, wb, QUnit ) {
	'use strict';

	/**
	 * Generates a referenceview widget suitable for testing.
	 *
	 * @param {Object} [options]
	 * @return {jQuery}
	 */
	function createReferenceview( options ) {
		options = $.extend( {
			getAdder: function() {
				return {
					destroy: function() {}
				};
			},
			listItemAdapter: wb.tests.getMockListItemAdapter(
				'snaklistview',
				function() {
					this.enterNewItem = function() {
						return $.Deferred().resolve( {
							data: function() {
								return { focus: function() {} };
							}
						} ).promise();
					};
					this.isValid = function() {
						return false;
					};
					this.stopEditing = function() {};
					this.value = function() {
						return this.options.value;
					};
				}
			)
		}, options );

		return $( '<div/>' )
			.addClass( 'test_referenceview' )
			.referenceview( options );
	}

	QUnit.module( 'jquery.wikibase.referenceview', window.QUnit.newMwEnvironment( {
		teardown: function() {
			$( '.test_referenceview' ).each( function( i, node ) {
				var $node = $( node ),
					referenceview = $node.data( 'referenceview' );

				if ( referenceview ) {
					referenceview.destroy();
				}

				$node.remove();
			} );
		}
	} ) );

	QUnit.test( 'Initialize and destroy', function( assert ) {
		assert.expect( 5 );
		var $node = createReferenceview(),
			referenceview = $node.data( 'referenceview' );

		assert.ok(
			referenceview !== undefined,
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

		assert.ok(
			referenceview.$listview.data( 'listview' ),
			'Initialized listview.'
		);

		referenceview.destroy();

		assert.strictEqual(
			referenceview.$listview, null,
			'Destroyed listview.'
		);
	} );

	QUnit.test( 'is initialized with a value', function( assert ) {
		assert.expect( 1 );
		var $node = createReferenceview( {
				value: new wb.datamodel.Reference( new wb.datamodel.SnakList( [
					new wb.datamodel.PropertyNoValueSnak( 'P1' )
				] ) )
			} ),
			referenceview = $node.data( 'referenceview' );

		assert.strictEqual(
			referenceview.value().getSnaks().length,
			1,
			'Referenceview contains a reference.'
		);
	} );

	QUnit.test( 'allows to enter new item', function( assert ) {
		assert.expect( 2 );
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

	QUnit.test( 'allows to stop editing', function( assert ) {
		assert.expect( 2 );
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

} )( jQuery, wikibase, QUnit );
