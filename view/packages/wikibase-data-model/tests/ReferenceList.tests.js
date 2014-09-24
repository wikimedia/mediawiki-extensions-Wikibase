/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.ReferenceList' );

/**
 * @return {wikibase.datamodel.ReferenceList}
 */
function getDefaultReferenceList() {
	return new wb.datamodel.ReferenceList( [
		new wb.datamodel.Reference(
			new wb.datamodel.SnakList(),
			'i am a hash'
		),
		new wb.datamodel.Reference(
			new wb.datamodel.SnakList(),
			'i am another hash'
		),
		new wb.datamodel.Reference()
	] );
}

QUnit.test( 'Constructor', function( assert ) {
	assert.ok(
		getDefaultReferenceList() instanceof wb.datamodel.ReferenceList,
		'Instantiated ReferenceList.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.ReferenceList( ['string1', 'string2'] );
		},
		'Throwing error when trying to instantiate ReferenceList with other than Reference objects.'
	);
} );

QUnit.test( 'hasReference()', function( assert ) {
	assert.ok(
		getDefaultReferenceList().hasReference(
			new wb.datamodel.Reference(
				new wb.datamodel.SnakList(),
				'i am a hash'
			)
		),
		'Verified hasReference() returning TRUE.'
	);

	assert.ok(
		!getDefaultReferenceList().hasReference(
			new wb.datamodel.Reference(
				new wb.datamodel.SnakList(),
				'i am a hash not in the default list'
			)
		),
		'Verified hasReference() returning FALSE.'
	);
} );

QUnit.test( 'addReference() & length attribute', function( assert ) {
	var referenceList = getDefaultReferenceList(),
		newReference = new wb.datamodel.Reference(
			new wb.datamodel.SnakList(),
			'i am a hash not yet in the list'
		);

	assert.equal(
		referenceList.length,
		3,
		'ReferenceList contains 3 Reference objects.'
	);

	referenceList.addReference( newReference );

	assert.ok(
		referenceList.hasReference( newReference ),
		'Added Reference.'
	);

	assert.equal(
		referenceList.length,
		4,
		'Increased length.'
	);
} );

QUnit.test( 'removeReference()', function( assert ) {
	var referenceList = getDefaultReferenceList();

	assert.equal(
		referenceList.length,
		3,
		'ReferenceList contains 3 Reference objects.'
	);

	assert.throws(
		function() {
			referenceList.removeReference( new wb.datamodel.Reference(
				new wb.datamodel.SnakList(),
				'i am a hash not existing in the list'
			) );
		},
		'Throwing error when trying to remove a Reference not set.'
	);

	var referenceInList = referenceList.getReferences()[1];

	referenceList.removeReference( referenceInList );

	assert.ok(
		!referenceList.hasReference( referenceInList ),
		'Removed Reference.'
	);

	assert.equal(
		referenceList.length,
		2,
		'ReferenceList contains 2 Reference objects.'
	);
} );

QUnit.test( 'isEmpty()', function( assert ) {
	var referenceList = new wb.datamodel.ReferenceList(),
		reference = new wb.datamodel.Reference( new wb.datamodel.SnakList(), 'i am a hash' );

	assert.ok(
		referenceList.isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	referenceList.addReference( reference );

	assert.ok(
		!referenceList.isEmpty(),
		'Verified isEmpty() returning FALSE.'
	);

	referenceList.removeReference( reference );

	assert.ok(
		referenceList.isEmpty(),
		'TRUE after removing last Reference.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	var referenceList = getDefaultReferenceList();

	assert.ok(
		referenceList.equals( getDefaultReferenceList() ),
		'Verified equals() retuning TRUE.'
	);

	referenceList.addReference(
		new wb.datamodel.Reference( new wb.datamodel.SnakList(), 'i am a hash not in the list' )
	);

	assert.ok(
		!referenceList.equals( getDefaultReferenceList() ),
		'FALSE after adding another Reference object.'
	);
} );

QUnit.test( 'indexOf()', function( assert ) {
	var referenceList = getDefaultReferenceList();

	assert.strictEqual(
		referenceList.indexOf( new wb.datamodel.Reference(
			new wb.datamodel.SnakList(),
			'i am another hash'
		) ),
		1,
		'Retrieved correct index.'
	);
} );

}( wikibase, QUnit ) );
