/**
 * QUnit tests for wikibase.SnakList
 * @see https://www.mediawiki.org/wiki/Extension:Wikibase
 *
 * @since 0.4
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( wb, dv, $, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.datamodel.SnakList.js', QUnit.newMwEnvironment() );

	var snaks = [
		new wb.PropertyNoValueSnak( '9001' ),
		new wb.PropertySomeValueSnak( '42' ),
		new wb.PropertySomeValueSnak( '42' ), // two times 42!
		new wb.PropertyValueSnak( '42', new dv.StringValue( '~=[,,_,,]:3' ) )
	];
	var anotherSnak = new wb.PropertySomeValueSnak( 'p1' ),
		anotherSnak2 = new wb.PropertySomeValueSnak( 'p2' );

	QUnit.test( 'SnakList constructor', function( assert ) {
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
				var newList = new wb.SnakList( 'foo' );
			},
			'Can not create SnakList with strange constructor argument'
		);
	} );

	QUnit.test( 'SnakList list operations', function( assert ) {
		var newSnakList = new wb.SnakList( snaks ),
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

		assert.ok(
			newSnakList.addSnak( anotherSnak ) && newSnakList.length === initialLength + 1,
			'Another snak added, length attribute increased by one'
		);

		assert.ok(
			newSnakList.hasSnak( anotherSnak ),
			'Newly added Snak recognized as one of the list\'s own Snaks now'
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
				newSnakList.addSnak( 'foo' )
			},
			'Can not add some strange thing to the Snak list'
		);
	} );

}( wikibase, dataValues, jQuery, QUnit ) );
