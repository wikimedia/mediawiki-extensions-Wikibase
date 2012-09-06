/**
 * QUnit tests for input interface for property edit tool which is handling lists
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.1
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
'use strict';


( function ( mw, wb, $ ) {
	module( 'wikibase.ui.PropertyEditTool.EditableValue.ListInterface', window.QUnit.newWbEnvironment( {
		setup: function() {
			this.node = $( '<div><ul><li>Y</li><li>Z</li><li><!--empty--></li><li>A</li></ul></div>', { id: 'subject' } );
			this.subject = new wb.ui.PropertyEditTool.EditableValue.ListInterface( this.node );

			ok(
				this.subject._subject[0] === this.node[0],
				'validated subject'
			);

		},
		teardown: function() {
			this.subject.destroy();

			equal(
				this.subject.site,
				null,
				'destroyed object'
			);

			this.node = null;
			this.subject = null;
		}
	} ) );


	test( 'basic', function() {

		ok(
			! this.subject.isEmpty(),
			'not considered empty'
		);

		equal(
			this.subject.getValue().join( '|' ),
			'Y|Z|A',
			'getValue() value equals initial value but sorted'
		);

		equal(
			this.subject.setValue( [ '3', '2', '', '1' ] ).join( '|' ),
			'3|2|1',
			'set new value, normalized it'
		);

	} );

	test( 'valueCompare()', function() {

		ok(
			this.subject.valueCompare( [ 'a', 'b' ], [ 'a', 'b' ] ),
			'simple strings, different order, equal'
		);

		ok(
			! this.subject.valueCompare( [ 'a', 'b' ], [ 'a', 'b', 'c' ] ),
			'more values in first argument, not equal'
		);

		ok(
			! this.subject.valueCompare( [ 'a', 'b', 'c' ], [ 'a', 'b' ] ),
			'more values in second argument, not equal'
		);

		ok(
			! this.subject.valueCompare( [ 'a' ] ),
			'value given, not empty'
		);

		ok(
			this.subject.valueCompare( [] ),
			'empty array considered empty'
		);

		ok(
			this.subject.valueCompare( [ '', '' ] ),
			'array with empty strings, considered empty'
		);

	} );

	test( 'checking for new/removed values during edit mode', function() {
		var self = this;
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
			ok(
				self.subject.valueCompare(
					self.subject.setValue( initialItems ),
					initialItems
				),
				'Items [' + initialItems.toString() + '] set properly (outside edit mode)'
			);

			ok(
				self.subject.startEditing(),
				'Started edit mode'
			);

			ok(
				self.subject.valueCompare(
					self.subject.setValue( setItems ),
					setItems
				),
				'Set values [' + setItems.toString() + '] in edit mode'
			);

			deepEqual(
				self.subject.getNewItems(),
				addedItems,
				'items [' + addedItems.toString() + '] are recognized as new items by getNewItems()'
			);

			deepEqual(
				self.subject.getRemovedItems(),
				removedItems,
				'items [' + removedItems.toString() + '] are recognized as new items by getRemovedItems()'
			);

			ok(
				!self.subject.stopEditing(false), // close edit mode for next test
				'Stopped edit mode'
			);
		}

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

	test( 'checking for new/removed values during edit mode', function() {
		deepEqual(
			this.subject.getNewItems(),
			[],
			'getNewItems() returns empty array in non-edit mode'
		);
		deepEqual(
			this.subject.getRemovedItems(),
			[],
			'getRemovedItems() returns empty array in non-edit mode'
		);
	} );

	}( mediaWiki, wikibase, jQuery ) );
