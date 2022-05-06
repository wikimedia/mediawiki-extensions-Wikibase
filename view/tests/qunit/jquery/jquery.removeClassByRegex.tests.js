/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner
 */
( function () {
	'use strict';

	QUnit.module( 'jquery.removeClassByRegex' );

	QUnit.test( 'Basics', function ( assert ) {
		var classes = [ 'a11a', 'bbb', 'c333', 'dddd', 'e', '6', '7' ];
		var $subject = $( '<div>' ).addClass( classes.join( '   ' ) ); // should also work with more than one space

		assert.strictEqual(
			$subject.attr( 'class' ).split( /\s+/ ).length,
			classes.length,
			'number of classes ok'
		);

		assert.true(
			$subject.removeClassByRegex( /abcdefgh/ ) instanceof $,
			'jQuery.removeClassByRegex() returns instance of jQuery'
		);

		assert.strictEqual(
			$subject.removeClassByRegex( /abcdefgh/ ).attr( 'class' ),
			classes.join( ' ' ),
			'non-matching regex, all classes should still be there'
		);

		assert.strictEqual(
			$subject.clone().removeClassByRegex( /.*/ ).attr( 'class' ),
			'',
			'removed all classes (from a clone)'
		);

		assert.strictEqual(
			$subject.clone().removeClassByRegex( /\d+/ ).attr( 'class' ),
			'bbb dddd e',
			'removed all classes with numbers in it (from a clone)'
		);

		assert.strictEqual(
			$( '<div>' ).removeClassByRegex( /foo/ ).attr( 'class' ),
			undefined,
			'jQuery.removeClassByRegex() does not add the "class" attribute when not present'
		);
	} );

	QUnit.test( 'Multiple elements', function ( assert ) {
		var $subject = $( '<div>' ).addClass( 'A B C 1 2 3' )
			.add( $( '<div>' ).addClass( 'AA  BB  CC  11  22  33' ) )
			.add( $( '<div>' ).addClass( 'AAA BBB CCC 111 222 333' ) );

		assert.true(
			$subject.removeClassByRegex( /abcdefgh/ ) instanceof $,
			'jQuery.removeClassByRegex() returns instance of jQuery'
		);

		var tmp = $subject.clone().removeClassByRegex( /.*/ );
		assert.strictEqual(
			$( tmp[ 0 ] ).attr( 'class' ) + $( tmp[ 1 ] ).attr( 'class' ) + $( tmp[ 2 ] ).attr( 'class' ),
			'',
			'removed all classes from all three elements (from a clone)'
		);

		tmp = $subject.clone().removeClassByRegex( /^\d+$/ );
		assert.strictEqual(
			[
				$( tmp[ 0 ] ).attr( 'class' ),
				$( tmp[ 1 ] ).attr( 'class' ),
				$( tmp[ 2 ] ).attr( 'class' )
			].join( '_' ),
			'A B C_AA BB CC_AAA BBB CCC',
			'removed all numeric classes from all three elements (from a clone)'
		);
	} );

}() );
