/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, dv, $, QUnit ) {
	'use strict';

QUnit.module( 'wikibase.datamodel.SnakList' );

var testSets = [
	[],
	[
		new wb.datamodel.PropertyNoValueSnak( 'P1' ),
		new wb.datamodel.PropertySomeValueSnak( 'P2' ),
		new wb.datamodel.PropertySomeValueSnak( 'p2' ), // same Snak
		new wb.datamodel.PropertyValueSnak( 'P2', new dv.StringValue( 'string' ) )
	],
	[
		new wb.datamodel.PropertyValueSnak( 'P1', new dv.StringValue( 'a' ) ),
		new wb.datamodel.PropertyValueSnak( 'P1', new dv.StringValue( 'b' ) ),
		new wb.datamodel.PropertyValueSnak( 'P2', new dv.StringValue( 'c' ) ),
		new wb.datamodel.PropertyValueSnak( 'P2', new dv.StringValue( 'd' ) ),
		new wb.datamodel.PropertyValueSnak( 'P2', new dv.StringValue( 'e' ) ),
		new wb.datamodel.PropertyValueSnak( 'P3', new dv.StringValue( 'f' ) ),
		new wb.datamodel.PropertyValueSnak( 'P4', new dv.StringValue( 'g' ) )
	]
];

/**
 * Returns the concatenated string values of a snak list's snaks.
 *
 * @param {wikibase.datamodel.SnakList} snakList
 * @return {string}
 */
function snakOrder( snakList ) {
	var snakValues = [];

	snakList.each( function( i, snak ) {
		snakValues.push( snak.getValue().getValue() );
	} );

	return snakValues.join( '' );
}

QUnit.test( 'Constructor', function( assert ) {
	assert.expect( 6 );
	for( var i = 0; i < testSets.length; i++ ) {
		var snakList = new wb.datamodel.SnakList( testSets[i] );

		assert.ok(
			snakList instanceof wb.datamodel.SnakList,
			'Test set #' + i + ': Created instance.'
		);

		assert.equal(
			snakList.length,
			testSets[i].length,
			'Test set #' + i + ': Verified length.'
		);
	}
} );

QUnit.test( 'getFilteredSnakList()', function( assert ) {
	assert.expect( 11 );
	assert.throws(
		function() {
			var snakList = new wb.datamodel.SnakList( [] );
			snakList.getFilteredSnakList();
		},
		'getFilteredSnakList() throws an error when called with null.'
	);

	for( var i = 0; i < testSets.length; i++ ) {
		var snakList = new wb.datamodel.SnakList( testSets[i] );

		assert.strictEqual(
			snakList.getFilteredSnakList( 'P9999' ).length,
			0,
			'No filtered SnakList returned for an empty SnakList.'
		);

		var groupedSnakLists = {},
			propertyId;

		for( var j = 0; j < testSets[i].length; j++ ) {
			propertyId = testSets[i][j].getPropertyId();
			if( !groupedSnakLists[propertyId] ) {
				groupedSnakLists[propertyId] = new wb.datamodel.SnakList();
			}
			groupedSnakLists[propertyId].addItem( testSets[i][j] );
		}

		for( propertyId in groupedSnakLists ) {
			assert.ok(
				snakList.getFilteredSnakList( propertyId ).equals( groupedSnakLists[propertyId] ),
				'Test set #' + i + ': Verified result of getFilteredSnakList() (property id: '
					+ propertyId + ').'
			);
		}
	}
} );

QUnit.test( 'merge()', function( assert ) {
	assert.expect( 6 );
	for( var i = 0; i < testSets.length; i++ ) {
		var snakList = new wb.datamodel.SnakList(),
			newSnak = new wb.datamodel.PropertyNoValueSnak( 'P10' );

		snakList.merge( new wb.datamodel.SnakList( testSets[i] ) );

		assert.ok(
			snakList.equals( new wb.datamodel.SnakList( testSets[i] ) ),
			'Merged SnakList into existing SnakList.'
		);

		snakList.merge( new wb.datamodel.SnakList( [newSnak] ) );

		var extendedSnakList = new wb.datamodel.SnakList( testSets[i] );
		extendedSnakList.addItem( newSnak );

		assert.ok(
			snakList.equals( extendedSnakList ),
			'Merged in another SnakList.'
		);
	}
} );

}( wikibase, dataValues, jQuery, QUnit ) );
