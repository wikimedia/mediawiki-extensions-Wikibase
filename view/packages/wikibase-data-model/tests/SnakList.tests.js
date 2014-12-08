/**
 * @licence GNU GPL v2+
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
	for( var i = 0; i < testSets.length; i++ ) {
		var snakList = new wb.datamodel.SnakList( testSets[i] );

		assert.ok(
			snakList.getFilteredSnakList() instanceof wb.datamodel.SnakList,
			'Returned SnakList object when issuing getFilteredSnakList() without parameter.'
		);

		assert.strictEqual(
			snakList.getFilteredSnakList( 'P9999' ).length,
			0,
			'No filtered SnakList returned for an empty SnakList.'
		);

		assert.ok(
			snakList.getFilteredSnakList().equals( new wb.datamodel.SnakList( snakList.toArray() ) ),
			'Returning SnakList clone when issuing getFilteredSnakList() without parameter.'
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

QUnit.test( 'getValidMoveIndices()', function( assert ) {
	var snaks = testSets[2],
		snakList = new wb.datamodel.SnakList( snaks );

	/**
	 * Expected indices where the individual snaks (with or without its groups) may be moved to.
	 * @property {number[][]}
	 */
	var validIndices = [
		[1, 5, 6, 7],
		[0, 5, 6, 7],
		[0, 3, 4, 6, 7],
		[0, 2, 4, 6, 7],
		[0, 2, 3, 6, 7],
		[0, 2, 7],
		[0, 2, 5]
	];

	for( var i = 0; i < validIndices.length; i++ ) {
		assert.deepEqual(
			snakList.getValidMoveIndices( snaks[i] ),
			validIndices[i],
			'Verified indices example Snak #' + i + ' may be moved to.'
		);
	}

	snakList = new wb.datamodel.SnakList(
		[ new wb.datamodel.PropertyValueSnak( 'P1',  new dv.StringValue( 'a' ) ) ]
	);

	assert.strictEqual(
		snakList.getValidMoveIndices( snakList.toArray()[0] ).length,
		0,
		'No indices returned when SnakList does not contain more than one Snak.'
	);

} );

QUnit.test( 'move()', function( assert ) {
	var snaks = testSets[2],
		snakList;

	/**
	 * Array of test case definitions. Test case definition structure:
	 * [0] => Index of element to move
	 * [1] => Index where to move element
	 * [2] => Expected result when concatenating the string values of the SnakList's Snaks.
	 * @property {*[][]}
	 */
	var testCases = [
		[ 0, 1, 'bacdefg' ],
		[ 0, 5, 'cdeabfg' ],
		[ 0, 6, 'cdefabg' ],
		[ 0, 7, 'cdefgab' ],
		[ 1, 0, 'bacdefg' ],
		[ 1, 5, 'cdeabfg' ],
		[ 1, 6, 'cdefabg' ],
		[ 1, 7, 'cdefgab' ],
		[ 2, 0, 'cdeabfg' ],
		[ 2, 3, 'abdcefg' ],
		[ 2, 4, 'abdecfg' ],
		[ 2, 6, 'abfcdeg' ],
		[ 2, 7, 'abfgcde' ],
		[ 3, 0, 'cdeabfg' ],
		[ 3, 2, 'abdcefg' ],
		[ 3, 4, 'abcedfg' ],
		[ 3, 6, 'abfcdeg' ],
		[ 3, 7, 'abfgcde' ],
		[ 4, 0, 'cdeabfg' ],
		[ 4, 2, 'abecdfg' ],
		[ 4, 3, 'abcedfg' ],
		[ 4, 6, 'abfcdeg' ],
		[ 4, 7, 'abfgcde' ],
		[ 5, 0, 'fabcdeg' ],
		[ 5, 2, 'abfcdeg' ],
		[ 5, 7, 'abcdegf' ],
		[ 6, 0, 'gabcdef' ],
		[ 6, 2, 'abgcdef' ],
		[ 6, 5, 'abcdegf' ]
	];

	for( var i = 1; i < testCases.length; i++ ) {
		snakList = new wb.datamodel.SnakList( snaks );

		snakList.move( snaks[testCases[i][0]], testCases[i][1] );

		assert.equal(
			snakOrder( snakList ),
			testCases[i][2],
			'Verified moving a Snak with test set #' + i + '.'
		);
	}

	snakList = new wb.datamodel.SnakList( snaks );
	snakList.move( snaks[0], 0 );

	assert.equal(
		snakOrder( snakList ),
		'abcdefg',
		'Nothing changed when trying to move a Snak to an index it already has.'
	);

	assert.throws(
		function() {
			snakList = new wb.datamodel.SnakList( snaks );
			snakList.move( 0, 4 );
		},
		'move() throws an error when trying to move a Snak to an invalid index.'
	);
} );

QUnit.test( 'moveUp() and moveDown()', function( assert ) {
	var snaks = testSets[2],
		snakList;

	/**
	 * Array of test case definitions for moveUp() and moveDown() methods. Test case definition
	 * structure:
	 * [0] => Resulting order after moving the element having the same index in the SnakList up.
	 * [1] => Resulting order after moving the element having the same index in the SnakList down.
	 * @property {string[][]}
	 */
	var testCases = [
		['abcdefg', 'bacdefg' ],
		['bacdefg', 'cdeabfg' ],
		['cdeabfg', 'abdcefg' ],
		['abdcefg', 'abcedfg' ],
		['abcedfg', 'abfcdeg' ],
		['abfcdeg', 'abcdegf' ],
		['abcdegf', 'abcdefg' ]
	];

	for( var i = 0; i < testCases.length; i++ ) {
		snakList = new wb.datamodel.SnakList( snaks );

		assert.equal(
			snakOrder( snakList.moveUp( snaks[i] ) ),
			testCases[i][0],
			'Verified result of moveUp() with test set #' + i + '.'
		);

		snakList = new wb.datamodel.SnakList( snaks );

		assert.equal(
			snakOrder( snakList.moveDown( snaks[i] ) ),
			testCases[i][1],
			'Verified result of moveDown() with test set #' + i + '.'
		);

	}
} );

}( wikibase, dataValues, jQuery, QUnit ) );
