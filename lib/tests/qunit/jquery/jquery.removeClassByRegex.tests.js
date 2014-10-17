/**
 * @licence GNU GPL v2+
 * @author Daniel Werner
 */
( function( $, QUnit ) {
	'use strict';

QUnit.module( 'jquery.removeClassByRegex', QUnit.newMwEnvironment() );

QUnit.test( 'Basics', function( assert ) {
	var classes = [ 'a11a', 'bbb', 'c333', 'dddd', 'e', '6', '7' ];
	var subject = $( '<div>', {
		'class': classes.join( '   ' ) // should also work with more than one space
	} );

	assert.equal(
		subject.attr( 'class' ).split( /\s+/ ).length,
		classes.length,
		'number of classes ok'
	);

	assert.ok(
		subject.removeClassByRegex( /abcdefgh/ ) instanceof jQuery,
		'jQuery.removeClassByRegex() returns instance of jQuery'
	);

	assert.equal(
		subject.removeClassByRegex( /abcdefgh/ ).attr( 'class' ),
		classes.join( ' ' ),
		'non-matching regex, all classes should still be there'
	);

	assert.equal(
		subject.clone().removeClassByRegex( /.*/ ).attr( 'class' ),
		'',
		'removed all classes (from a clone)'
	);

	assert.equal(
		subject.clone().removeClassByRegex( /\d+/ ).attr( 'class' ),
		'bbb dddd e',
		'removed all classes with numbers in it (from a clone)'
	);
} );

QUnit.test( 'Multiple elements', function( assert ) {

	var subject = $(
		'<div>', { 'class': 'A B C 1 2 3' }
	).add(
		'<div>', { 'class': 'AA  BB  CC  11  22  33' }
	).add(
		'<div>', { 'class': 'AAA BBB CCC 111 222 333' }
	);

	assert.ok(
		subject.removeClassByRegex( /abcdefgh/ ) instanceof jQuery,
		'jQuery.removeClassByRegex() returns instance of jQuery'
	);

	var tmp = subject.clone().removeClassByRegex( /.*/ );
	assert.equal(
		$( tmp[0] ).attr( 'class' ) + $( tmp[1] ).attr( 'class' ) + $( tmp[2] ).attr( 'class' ),
		'',
		'removed all classes from all three elements (from a clone)'
	);

	tmp = subject.clone().removeClassByRegex( /^\d+$/ );
	assert.equal(
		[
			$( tmp[0] ).attr( 'class' ),
			$( tmp[1] ).attr( 'class' ),
			$( tmp[2] ).attr( 'class' )
		].join( '_' ),
		'A B C_AA BB CC_AAA BBB CCC',
		'removed all numeric classes from all three elements (from a clone)'
	);
} );

}( jQuery, QUnit ) );
