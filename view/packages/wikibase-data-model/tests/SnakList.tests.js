/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( dv, $, QUnit ) {
	'use strict';

var SnakList = require( '../src/SnakList.js' ),
	PropertyValueSnak = require( '../src/PropertyValueSnak.js' ),
	PropertySomeValueSnak = require( '../src/PropertySomeValueSnak.js' ),
	PropertyNoValueSnak = require( '../src/PropertyNoValueSnak.js' );

QUnit.module( 'SnakList' );

var testSets = [
	[],
	[
		new PropertyNoValueSnak( 'P1' ),
		new PropertySomeValueSnak( 'P2' ),
		new PropertySomeValueSnak( 'p2' ), // same Snak
		new PropertyValueSnak( 'P2', new dv.StringValue( 'string' ) )
	],
	[
		new PropertyValueSnak( 'P1', new dv.StringValue( 'a' ) ),
		new PropertyValueSnak( 'P1', new dv.StringValue( 'b' ) ),
		new PropertyValueSnak( 'P2', new dv.StringValue( 'c' ) ),
		new PropertyValueSnak( 'P2', new dv.StringValue( 'd' ) ),
		new PropertyValueSnak( 'P2', new dv.StringValue( 'e' ) ),
		new PropertyValueSnak( 'P3', new dv.StringValue( 'f' ) ),
		new PropertyValueSnak( 'P4', new dv.StringValue( 'g' ) )
	]
];

/**
 * Returns the concatenated string values of a snak list's snaks.
 *
 * @param {SnakList} snakList
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
		var snakList = new SnakList( testSets[i] );

		assert.ok(
			snakList instanceof SnakList,
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
			var snakList = new SnakList( [] );
			snakList.getFilteredSnakList();
		},
		'getFilteredSnakList() throws an error when called with null.'
	);

	for( var i = 0; i < testSets.length; i++ ) {
		var snakList = new SnakList( testSets[i] );

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
				groupedSnakLists[propertyId] = new SnakList();
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
		var snakList = new SnakList(),
			newSnak = new PropertyNoValueSnak( 'P10' );

		snakList.merge( new SnakList( testSets[i] ) );

		assert.ok(
			snakList.equals( new SnakList( testSets[i] ) ),
			'Merged SnakList into existing SnakList.'
		);

		snakList.merge( new SnakList( [newSnak] ) );

		var extendedSnakList = new SnakList( testSets[i] );
		extendedSnakList.addItem( newSnak );

		assert.ok(
			snakList.equals( extendedSnakList ),
			'Merged in another SnakList.'
		);
	}
} );

}( dataValues, jQuery, QUnit ) );
