/**
 * QUnit tests for Wikibase jQuery plugins
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

( function() {
	module( 'wikibase.utilities.jQuery', window.QUnit.newWbEnvironment() );

	test( 'Basic jQuery.removeClassByRegex() tests', function() {

		var classes = [ 'a11a', 'bbb', 'c333', 'dddd', 'e', '6', '7' ];
		var subject = $( '<div/>', {
			'class': classes.join( '   ' ) // should also work with more than one space
		} );

		equal(
			subject.attr( 'class' ).split( /\s+/ ).length,
			classes.length,
			'number of classes ok'
		);

		ok(
			subject.removeClassByRegex( /abcdefgh/ ) instanceof jQuery,
			'jQuery.removeClassByRegex() returns instance of jQuery'
		);

		equal(
			subject.removeClassByRegex( /abcdefgh/ ).attr( 'class' ),
			classes.join( ' ' ),
			'non-matching regex, all classes should still be there'
		);

		equal(
			subject.clone().removeClassByRegex( /.*/ ).attr( 'class' ),
			'',
			'removed all classes (from a clone)'
		);

		equal(
			subject.clone().removeClassByRegex( /\d+/ ).attr( 'class' ),
			'bbb dddd e',
			'removed all classes with numbers in it (from a clone)'
		);
	} );

	test( 'jQuery.removeClassByRegex() test with multiple elements in the jQuery object', function() {

		var subject = $(
			'<div/>', { 'class': 'A B C 1 2 3' }
		).add(
			'<div/>', { 'class': 'AA  BB  CC  11  22  33' }
		).add(
			'<div/>', { 'class': 'AAA BBB CCC 111 222 333' }
		);

		ok(
			subject.removeClassByRegex( /abcdefgh/ ) instanceof jQuery,
			'jQuery.removeClassByRegex() returns instance of jQuery'
		);

		var tmp = subject.clone().removeClassByRegex( /.*/ );
		equal(
			$( tmp[0] ).attr( 'class' ) + $( tmp[1] ).attr( 'class' ) + $( tmp[2] ).attr( 'class' ),
			'',
			'removed all classes from all three elements (from a clone)'
		);

		var tmp = subject.clone().removeClassByRegex( /^\d+$/ );
		equal(
			$( tmp[0] ).attr( 'class' ) + "_" + $( tmp[1] ).attr( 'class' ) + "_" + $( tmp[2] ).attr( 'class' ),
			'A B C_AA BB CC_AAA BBB CCC',
			'removed all numeric classes from all three elements (from a clone)'
		);
	} );

}() );
