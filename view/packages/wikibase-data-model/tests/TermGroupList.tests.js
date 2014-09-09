/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, $, QUnit ) {
'use strict';

QUnit.module( 'wikibase.datamodel.TermGroupList' );

function getDefaultTermGroupList() {
	return new wb.datamodel.TermGroupList( [
		new wb.datamodel.TermGroup( 'de', ['de-string'] ),
		new wb.datamodel.TermGroup( 'en', ['en-string'] )
	] );
}

QUnit.test( 'Constructor', function( assert ) {
	assert.ok(
		getDefaultTermGroupList() instanceof wb.datamodel.TermGroupList,
		'Instantiated TermGroupList.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.TermGroupList( ['string1', 'string2'] );
		},
		'Throwing error when trying to instantiate TermGroupList without TermGroup objects.'
	);

	assert.throws(
		function() {
			return new wb.datamodel.TermGroupList( [
				new wb.datamodel.TermGroup( 'de', ['de-text1'] ),
				new wb.datamodel.TermGroup( 'de', ['de-text2'] )
			] );
		},
		'Throwing error when trying to instantiate a TermGroupList with multiple TermGroup objects '
		+ 'for one language.'
	);
} );

QUnit.test( 'getByLanguage()', function( assert ) {
	assert.ok(
		getDefaultTermGroupList().getByLanguage( 'en' ).equals(
			new wb.datamodel.TermGroup( 'en', ['en-string'] )
		),
		'Retrieved TermGroup object by language.'
	);

	assert.strictEqual(
		getDefaultTermGroupList().getByLanguage( 'does-not-exist' ),
		null,
		'Returning NULL when no TermGroup object is set for a particular language.'
	);
} );

QUnit.test( 'removeByLanguage() & length attribute', function( assert ) {
	var termGroupList = getDefaultTermGroupList();

	assert.equal(
		termGroupList.length,
		2,
		'TermGroupList contains 2 TermGroup objects.'
	);

	termGroupList.removeByLanguage( 'de' );

	assert.strictEqual(
		termGroupList.getByLanguage( 'de' ),
		null,
		'Removed TermGroup.'
	);

	assert.strictEqual(
		termGroupList.length,
		1,
		'TermGroupList contains 1 TermGroup object.'
	);

	termGroupList.removeByLanguage( 'does-not-exist' );

	assert.strictEqual(
		termGroupList.length,
		1,
		'TermGroupList contains 1 TermGroup object after trying to remove a TermGroup that is not '
		+ 'set.'
	);

	termGroupList.removeByLanguage( 'en' );

	assert.strictEqual(
		termGroupList.getByLanguage( 'en' ),
		null,
		'Removed TermGroup.'
	);

	assert.strictEqual(
		termGroupList.length,
		0,
		'TermGroupList is empty.'
	);
} );

QUnit.test( 'hasGroupForLanguage()', function( assert ) {
	assert.ok(
		getDefaultTermGroupList().hasGroupForLanguage( 'en' ),
		'Verified hasGroupForLanguage() returning TRUE.'
	);

	assert.ok(
		!getDefaultTermGroupList().hasGroupForLanguage( 'does-not-exist' ),
		'Verified hasGroupForLanguage() returning FALSE.'
	);
} );

QUnit.test( 'setGroup() & length attribute', function( assert ) {
	var termGroupList = getDefaultTermGroupList(),
		newEnTermGroup = new wb.datamodel.TermGroup( 'en', ['en-string-overwritten'] ),
		newTermGroup = new wb.datamodel.TermGroup( 'ar', ['ar-string'] ),
		emptyEnGroup = new wb.datamodel.TermGroup( 'en', [] );

	assert.ok(
		termGroupList.length,
		2,
		'TermGroupList contains 2 TermGroup objects.'
	);

	termGroupList.setGroup( newEnTermGroup );

	assert.ok(
		termGroupList.getByLanguage( 'en' ).equals( newEnTermGroup ),
		'Set new "en" TermGroup.'
	);

	assert.equal(
		termGroupList.length,
		2,
		'Length remains unchanged when overwriting a TermGroup.'
	);

	termGroupList.setGroup( newTermGroup );

	assert.ok(
		termGroupList.getByLanguage( 'ar' ).equals( newTermGroup ),
		'Added new TermGroup.'
	);

	assert.equal(
		termGroupList.length,
		3,
		'Increased length when adding new TermGroup.'
	);

	termGroupList.setGroup( emptyEnGroup );

	assert.strictEqual(
		termGroupList.getByLanguage( 'en' ),
		null,
		'Removed group by setting an empty group.'
	);

	assert.equal(
		termGroupList.length,
		2,
		'Decreased length after setting an empty group.'
	);

	assert.throws(
		function() {
			termGroupList.setGroup( ['string'] );
		},
		'Throwing error when trying to set a plain string array.'
	);
} );

QUnit.test( 'equals()', function( assert ) {
	var termGroupList = getDefaultTermGroupList();

	assert.ok(
		termGroupList.equals( getDefaultTermGroupList() ),
		'Verified equals() retuning TRUE.'
	);

	termGroupList.setGroup( new wb.datamodel.TermGroup( 'en', ['en-string-overwritten'] ) );

	assert.ok(
		!termGroupList.equals( getDefaultTermGroupList() ),
		'FALSE when a TermGroup has been overwritten.'
	);

	termGroupList = getDefaultTermGroupList();
	termGroupList.removeByLanguage( 'en' );

	assert.ok(
		!termGroupList.equals( getDefaultTermGroupList() ),
		'FALSE when a TermGroup has been removed.'
	);

	assert.ok(
		!termGroupList.equals( [
			getDefaultTermGroupList().getByLanguage( 'de' ),
			getDefaultTermGroupList().getByLanguage( 'en' )
		] ),
		'FALSE when submitting an array instead of a TermGroupList instance.'
	);
} );

QUnit.test( 'hasGroup()', function( assert ) {
	assert.ok(
		getDefaultTermGroupList().hasGroup( new wb.datamodel.TermGroup( 'de', ['de-string'] ) ),
		'Verified hasGroup() returning TRUE.'
	);

	assert.ok(
		!getDefaultTermGroupList().hasGroup(
			new wb.datamodel.TermGroup( 'de', ['does-not-exist'] )
		),
		'Verified hasGroup() returning FALSE.'
	);

	assert.throws(
		function() {
			getDefaultTermGroupList().hasGroup( ['de-text'] );
		},
		'Throwing error when submitting a string array.'
	);
} );

}( wikibase, jQuery, QUnit ) );
