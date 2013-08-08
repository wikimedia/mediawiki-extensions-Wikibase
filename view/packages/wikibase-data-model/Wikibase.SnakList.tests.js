/**
 * @since 0.4
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 * @author H. Snater < mediawiki@snater.com >
 */

( function( wb, dv, $, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.datamodel.SnakList.js', QUnit.newMwEnvironment() );

	var snakSets = [
		[
			new wb.PropertyNoValueSnak( 'p9001' ),
			new wb.PropertySomeValueSnak( 'p42' ),
			new wb.PropertySomeValueSnak( 'p42' ), // two times 42!
			new wb.PropertyValueSnak( 'p42', new dv.StringValue( '~=[,,_,,]:3' ) )
		],
		[
			new wb.PropertyValueSnak( 'p1', new dv.StringValue( 'a' ) ),
			new wb.PropertyValueSnak( 'p1', new dv.StringValue( 'b' ) ),
			new wb.PropertyValueSnak( 'p2', new dv.StringValue( 'a' ) ),
			new wb.PropertyValueSnak( 'p2', new dv.StringValue( 'b' ) ),
			new wb.PropertyValueSnak( 'p2', new dv.StringValue( 'c' ) ),
			new wb.PropertyValueSnak( 'p3', new dv.StringValue( 'a' ) ),
			new wb.PropertyValueSnak( 'p4', new dv.StringValue( 'a' ) )
		]
	];
	var anotherSnak = new wb.PropertySomeValueSnak( 'p1' ),
		anotherSnak2 = new wb.PropertySomeValueSnak( 'p2' );

	QUnit.test( 'SnakList constructor', function( assert ) {
		var snaks = snakSets[0];

		var constructorArgs = [
			[ snaks[0], 1, 'single wb.Snak' ],
			[ snaks, 3, 'array of wb.Snak' ],
			[ undefined, 0, 'undefined' ],
			[ new wb.SnakList( snaks ), 3, 'wb.SnakList' ]
		];

		$.each( constructorArgs, function( i, args ) {
			var newSnakList = new wb.SnakList( args[0] );

			assert.ok(
				newSnakList instanceof wb.SnakList,
				'Instance of wb.SnakList created with ' + args[2]
			);

			assert.ok(
				newSnakList.length === args[1],
				'Length of Snak list is accurate (' + args[1] + ' Snaks)'
			);

			var equalNewSnakList = new wb.SnakList( args[0] );
			assert.ok(
				newSnakList.equals( equalNewSnakList ) && equalNewSnakList.equals( newSnakList ),
				'Another instance of SnakList, created with same constructor arguments, is ' +
					'being considered equal to the first list.'
			);

			var newListJson = newSnakList.toJSON();
			assert.ok(
				$.isPlainObject( newListJson ),
				'Snak list\'s toJSON() returns plain object'
			);

			var newListArray = newSnakList.toArray();
			assert.ok(
				$.isArray( newListArray ) && newListArray.length === newSnakList.length,
				'Snak list\'s toArray() returns simple Array with same length as list'
			);
		} );

		assert.throws(
			function() {
				return new wb.SnakList( 'foo' );
			},
			'Can not create SnakList with strange constructor argument'
		);
	} );

	QUnit.test( 'newFromJSON()', function( assert ) {
		var snaks = snakSets[0],
			snakList = new wb.SnakList( snaks ),
			initialOrder = snakList.getPropertyOrder(),
			clonedSnakList = wb.SnakList.newFromJSON( snakList.toJSON() );

		assert.ok(
			snakList.equals( clonedSnakList ),
			'Cloned snak list using the JSON representation.'
		);

		var reorderedClone = wb.SnakList.newFromJSON( snakList.toJSON(), ['p42', 'p9001'] ),
			cloneOrder = reorderedClone.getPropertyOrder();

		assert.ok(
			snakList.equals( reorderedClone ),
			'Cloned snak list with applying a different property order.'
		);

		assert.ok(
			initialOrder[0] === cloneOrder[1] && initialOrder[1] === cloneOrder[0],
			'Verified differing property order.'
		);

	} );

	QUnit.test( 'SnakList list operations', function( assert ) {
		var snaks = snakSets[0],
			newSnakList = new wb.SnakList( snaks ),
			initialLength = newSnakList.length;

		assert.ok(
			!newSnakList.equals( new wb.SnakList() )
				&& !( new wb.SnakList() ).equals( newSnakList ),
			'Snak list is not equal to a new, empty Snak list'
		);

		assert.ok(
			newSnakList.hasSnak( snaks[0] ),
			'New Snak list recognizes a Snak from constructor array as one of its own'
		);

		assert.ok(
			!newSnakList.hasSnak( anotherSnak ),
			'New Snak list does not recognize another Snak, not in the list as one of its own'
		);

		assert.deepEqual(
			newSnakList.getPropertyOrder(),
			['p9001', 'p42'],
			'Verified property order.'
		);

		assert.ok(
			newSnakList.addSnak( anotherSnak ) && newSnakList.length === initialLength + 1,
			'Another snak added, length attribute increased by one'
		);

		assert.ok(
			newSnakList.hasSnak( anotherSnak ),
			'Newly added Snak recognized as one of the list\'s own Snaks now'
		);

		assert.deepEqual(
			newSnakList.getPropertyOrder(),
			['p9001', 'p42', 'p1'],
			'Verified property order.'
		);

		var clonedSnak = wb.Snak.newFromJSON( anotherSnak.toJSON() );
		assert.ok(
			newSnakList.hasSnak( clonedSnak ),
			'Snak same as newly added Snak recognized as one of the list\'s own Snaks now'
		);

		assert.ok(
			!newSnakList.addSnak( clonedSnak ) && newSnakList.length === initialLength + 1,
			'Try to add snak equal to last one, length did not increase again, Snak not added'
		);

		assert.ok(
			newSnakList.addSnak( anotherSnak2 ) && newSnakList.length === initialLength + 2,
			'Added another Snak. Basically for upcoming test to check whether indexes won\'t be' +
				'mixed up since we could have created a gap in the internal organization of the list'
		);

		assert.ok(
			newSnakList.removeSnak( clonedSnak ) && newSnakList.length === initialLength + 1,
			'Newly added Snak removed again (identified by cloned Snak, so we test for non === ' +
				'case; list length decreased by one'
		);

		assert.deepEqual(
			newSnakList.getPropertyOrder(),
			['p9001', 'p42', 'p2'],
			'Verified property order.'
		);

		var i = 0;
		newSnakList.each( function( index, snak ) {
			assert.equal(
				index,
				i++,
				'Given index in wb.SnakList.each() callback not incremented by more than one'
			);
			assert.ok(
				newSnakList.hasSnak( snak ),
				'Given wb.Snak in wb.SnakList.each() callback actually is member of related list'
			);
		} );

		assert.equal(
			i,
			newSnakList.length,
			'wb.SnakList.each() did iterate over all Snaks in the list'
		);

		var newListArray = newSnakList.toArray();
		newListArray.push( 'foo' );
		assert.ok(
			newSnakList.length === newListArray.length - 1,
			'Array returned by toArray() is not a reference to List\'s internal Snak array'
		);

		assert.throws(
			function() {
				newSnakList.addSnak( 'foo' );
			},
			'Can not add some strange thing to the Snak list'
		);
	} );

	QUnit.test( 'getFilteredSnakList()', function( assert ) {
		var snaks = snakSets[1],
			snakList = new wb.SnakList();

		assert.ok(
			snakList.getFilteredSnakList() instanceof wb.SnakList,
			'Returned SnakList object when issuing getFilteredSnakList without parameter.'
		);

		assert.equal(
			snakList.getFilteredSnakList( 'p42' ).length,
			0,
			'No filtered snak list returned for an empty snak list.'
		);

		snakList = new wb.SnakList( snaks );

		assert.ok(
			snakList.getFilteredSnakList().equals( snakList ),
			'Returning SnakList clone when issuing getFilteredSnakList without parameter.'
		);

		/**
		 * Indexed by property id, this object references the index of snaks belonging to the
		 * property group as to the array used as source for this test's SnakList object.
		 * @type {Object}
		 */
		var snakListGroups = {
			p1: [0, 1],
			p2: [2, 3, 4],
			p3: [5],
			p4: [6]
		};

		/**
		 * SnakList object containing the snaks grouped by property as to the snakListGroups
		 * variable specified above.
		 * @type {wikibase.SnakList}
		 */
		var groupedSnakList;

		for( var propertyId in snakListGroups ) {
			groupedSnakList = new wb.SnakList();

			for( var i = 0; i < snakListGroups[propertyId].length; i++ ) {
				groupedSnakList.addSnak( snaks[snakListGroups[propertyId][i]] );
			}

			assert.ok(
				snakList.getFilteredSnakList( propertyId ).equals( groupedSnakList ),
				'Verified result of getFilteredSnakList() (property id: ' + propertyId + ').'
			);
		}
	} );

	QUnit.test( 'add()', function( assert ) {
		var snaks = snakSets[1],
			snakList = new wb.SnakList();

		snakList.add( new wb.SnakList() );

		assert.equal(
			snakList.length,
			0,
			'Nothing changed when adding an empty snak list to an empty snak list.'
		);

		snakList.add( new wb.SnakList( snaks ) );

		assert.ok(
			snakList.equals( new wb.SnakList( snaks ) ),
			'Added snak list to existing snak list.'
		);

		snakList.add( new wb.SnakList() );

		assert.ok(
			snakList.equals( new wb.SnakList( snaks ) ),
			'Nothing changed when adding an empty snak list.'
		);

		snakList.add( new wb.SnakList( [ anotherSnak ] ) );

		var extendedSnakList = new wb.SnakList( snaks );
		extendedSnakList.addSnak( anotherSnak );

		assert.ok(
			snakList.equals( extendedSnakList ),
			'Added another snak list.'
		);
	} );

}( wikibase, dataValues, jQuery, QUnit ) );
