/**
 * QUnit tests for input interface for property edit tool which is handling lists
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */

( function( wb, $, QUnit, undefined ) {
	'use strict';

	/**
	 * Factory for creating a new ListInterface suited for testing.
	 *
	 * @param {jQuery} [$node]
	 * @return {wb.ui.PropertyEditTool.EditableValue.ListInterface}
	 */
	var newTestListInterface = function( $node ) {
		if ( $node === undefined ) {
			$node = $(
				'<div><ul><li>Y</li><li>Z</li><li><!--empty--></li><li>A</li></ul></div>',
				{ id: 'subject' }
			);
		}
		return new wb.ui.PropertyEditTool.EditableValue.ListInterface( $node );
	};

	QUnit.module( 'wikibase.ui.PropertyEditTool.EditableValue.ListInterface', QUnit.newWbEnvironment() );

	QUnit.test( 'basic', function( assert ) {
		var $node = $(
			'<div><ul><li>Y</li><li>Z</li><li><!--empty--></li><li>A</li></ul></div>',
			{ id: 'subject' }
		);
		var subject = newTestListInterface( $node );

		assert.ok(
			subject._subject[0] === $node[0],
			'validated subject'
		);

		assert.ok(
			!subject.isEmpty(),
			'not considered empty'
		);

		assert.equal(
			subject.getValue().join( '|' ),
			'Y|Z|A',
			'getValue() value equals initial value but sorted'
		);

		assert.equal(
			subject.setValue( [ '3', '2', '', '1' ] ).join( '|' ),
			'3|2|1',
			'set new value, normalized it'
		);

		subject.destroy();

		assert.equal(
			subject.site,
			null,
			'destroyed object'
		);
	} );

	QUnit.test( 'valueCompare()', function( assert ) {
		var subject = newTestListInterface();

		assert.ok(
			subject.valueCompare( [ 'a', 'b' ], [ 'a', 'b' ] ),
			'simple strings, different order, equal'
		);

		assert.ok(
			!subject.valueCompare( [ 'a', 'b' ], [ 'a', 'b', 'c' ] ),
			'more values in first argument, not equal'
		);

		assert.ok(
			!subject.valueCompare( [ 'a', 'b', 'c' ], [ 'a', 'b' ] ),
			'more values in second argument, not equal'
		);

		assert.ok(
			!subject.valueCompare( [ 'a' ] ),
			'value given, not empty'
		);

		assert.ok(
			subject.valueCompare( [] ),
			'empty array considered empty'
		);

		assert.ok(
			subject.valueCompare( [ '', '' ] ),
			'array with empty strings, considered empty'
		);
	} );

	QUnit.test( 'checking for new/removed values during edit mode', function( assert ) {
		var subject = newTestListInterface();
		/**
		 * Creates a new ListInterface, sets items initially, then starts edit mode and changes the set of items.
		 * After this the getRemovedItems() and getNewItems() functions will be tested.
		 *
		 * @param initialItems Array Items set before edit mode
		 * @param setItems Array Items set during edit mode
		 * @param addedItems Array
		 * @param removedItems Array
		 */
		var addRemoveItemsInEditModeTest = function( initialItems, setItems, addedItems, removedItems ) {
			assert.ok(
				subject.valueCompare(
					subject.setValue( initialItems ),
					initialItems
				),
				'Items [' + initialItems.toString() + '] set properly (outside edit mode)'
			);

			assert.ok(
				subject.startEditing(),
				'Started edit mode'
			);

			assert.ok(
				subject.valueCompare(
					subject.setValue( setItems ),
					setItems
				),
				'Set values [' + setItems.toString() + '] in edit mode'
			);

			assert.deepEqual(
				subject.getNewItems(),
				addedItems,
				'items [' + addedItems.toString() + '] are recognized as new items by getNewItems()'
			);

			assert.deepEqual(
				subject.getRemovedItems(),
				removedItems,
				'items [' + removedItems.toString() + '] are recognized as new items by getRemovedItems()'
			);

			assert.ok(
				!subject.stopEditing(false), // close edit mode for next test
				'Stopped edit mode'
			);
		};

		addRemoveItemsInEditModeTest(
			[],           // initial
			[ 'a', 'b' ], // set after entering edit mode
			[ 'a', 'b' ], // recognized as added
			[]            // recognized as removed
		);
		addRemoveItemsInEditModeTest(
			[ 'a', 'b', 'c' ],
			[],
			[],
			[ 'a', 'b', 'c' ]
		);
		addRemoveItemsInEditModeTest(
			[ 'a', 'b', 'c' ],
			[ 'a' ],
			[],
			[ 'b', 'c' ]
		);
		addRemoveItemsInEditModeTest(
			[ 'a' ],
			[ 'a', 'b', 'c' ],
			[ 'b', 'c' ],
			[]
		);
		addRemoveItemsInEditModeTest(
			[],
			[],
			[],
			[]
		);
		addRemoveItemsInEditModeTest(
			[ 'a', 'b', 'c' ],
			[ 'b', 'x', 'y' ],
			[ 'x', 'y' ],
			[ 'a', 'c' ]
		);
	} );

	QUnit.test( 'checking for new/removed values while not in edit mode', function( assert ) {
		var subject = newTestListInterface();

		assert.deepEqual(
			subject.getNewItems(),
			[],
			'getNewItems() returns empty array in non-edit mode'
		);
		assert.deepEqual(
			subject.getRemovedItems(),
			[],
			'getRemovedItems() returns empty array in non-edit mode'
		);
	} );

}( wikibase, jQuery, QUnit ) );
