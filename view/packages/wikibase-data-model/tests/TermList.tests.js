/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.TermList' );

function getDefaultTermList() {
	return new wb.datamodel.TermList( [
		new wb.datamodel.Term( 'de', 'de-string' ),
		new wb.datamodel.Term( 'en', 'en-string' )
	] );
}

QUnit.test( 'Constructor', function( assert ) {
	assert.ok(
		getDefaultTermList() instanceof wb.datamodel.TermList,
		'Instantiated TermList.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.TermList( ['string1', 'string2'] );
		},
		'Throwing error when trying to instantiate TermList without Term objects.'
	);
} );

QUnit.test( 'getByLanguage()', function( assert ) {
	assert.ok(
		getDefaultTermList().getByLanguage( 'en' ).equals(
			new wb.datamodel.Term( 'en', 'en-string' )
		),
		'Retrieved Term object by language.'
	);

	assert.strictEqual(
		getDefaultTermList().getByLanguage( 'does-not-exist' ),
		null,
		'Returning NULL when no Term object is set for a particular language.'
	);
} );

QUnit.test( 'removeByLanguage() & length attribute', function( assert ) {
	var termList = getDefaultTermList();

	assert.equal(
		termList.length,
		2,
		'TermList contains 2 Term objects.'
	);

	termList.removeByLanguage( 'de' );

	assert.strictEqual(
		termList.getByLanguage( 'de' ),
		null,
		'Removed Term.'
	);

	assert.strictEqual(
		termList.length,
		1,
		'TermList contains 1 Term object.'
	);

	termList.removeByLanguage( 'does-not-exist' );

	assert.strictEqual(
		termList.length,
		1,
		'TermList contains 1 Term object after trying to remove a Term that is not set.'
	);

	termList.removeByLanguage( 'en' );

	assert.strictEqual(
		termList.getByLanguage( 'en' ),
		null,
		'Removed Term.'
	);

	assert.strictEqual(
		termList.length,
		0,
		'TermList is empty.'
	);
} );

QUnit.test( 'hasTermForLanguage()', function( assert ) {
	assert.ok(
		getDefaultTermList().hasTermForLanguage( 'en' ),
		'Verified hasTermForLanguage() returning TRUE.'
	);

	assert.ok(
		!getDefaultTermList().hasTermForLanguage( 'does-not-exist' ),
		'Verified hasTermForLanguage() returning FALSE.'
	);
} );

QUnit.test( 'setTerm() & length attribute', function( assert ) {
	var termList = getDefaultTermList(),
		newEnTerm = new wb.datamodel.Term( 'en', 'en-string-overwritten' ),
		newTerm = new wb.datamodel.Term( 'ar', 'ar-string' );

	assert.equal(
		termList.length,
		2,
		'TermList contains 2 Term objects.'
	);

	termList.setTerm( newEnTerm );

	assert.ok(
		termList.getByLanguage( 'en' ).equals( newEnTerm ),
		'Set new "en" Term.'
	);

	assert.equal(
		termList.length,
		2,
		'Length remains unchanged when overwriting a Term.'
	);

	termList.setTerm( newTerm );

	assert.ok(
		termList.getByLanguage( 'ar' ).equals( newTerm ),
		'Added new Term.'
	);

	assert.equal(
		termList.length,
		3,
		'Increased length when adding new Term.'
	);

	assert.throws(
		function() {
			termList.setTerm( 'string' );
		},
		'Throwing error when trying to set a plain string.'
	);
} );

QUnit.test( 'isEmpty()', function( assert ) {
	var termList = new wb.datamodel.TermList();

	assert.ok(
		termList.isEmpty(),
		'Verified isEmpty() returning TRUE.'
	);

	termList.setTerm( new wb.datamodel.Term( 'de', 'de-string' ) );

	assert.ok(
		!termList.isEmpty(),
		'Verified isEmpty() returning FALSE.'
	);

	termList.removeByLanguage( 'de' );

	assert.ok(
		termList.isEmpty(),
		'TRUE after removing last Term.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	var termList = getDefaultTermList();

	assert.ok(
		termList.equals( getDefaultTermList() ),
		'Verified equals() retuning TRUE.'
	);

	termList.setTerm( new wb.datamodel.Term( 'en', 'en-string-overwritten' ) );

	assert.ok(
		!termList.equals( getDefaultTermList() ),
		'FALSE when a Term has been overwritten.'
	);

	termList = getDefaultTermList();
	termList.removeByLanguage( 'en' );

	assert.ok(
		!termList.equals( getDefaultTermList() ),
		'FALSE when a Term has been removed.'
	);

	assert.ok(
		!termList.equals( [
			getDefaultTermList().getByLanguage( 'de' ),
			getDefaultTermList().getByLanguage( 'en' )
		] ),
		'FALSE when submitting an array instead of a TermList instance.'
	);
} );

QUnit.test( 'hasTerm()', function( assert ) {
	assert.ok(
		getDefaultTermList().hasTerm( new wb.datamodel.Term( 'de', 'de-string' ) ),
		'Verified hasTerm() returning TRUE.'
	);

	assert.ok(
		!getDefaultTermList().hasTerm( new wb.datamodel.Term( 'de', 'does-not-exist' ) ),
		'Verified hasTerm() returning FALSE.'
	);

	assert.throws(
		function() {
			getDefaultTermList().hasTerm( 'de-text' );
		},
		'Throwing error when submitting a plain string.'
	);
} );

}( wikibase, QUnit ) );
