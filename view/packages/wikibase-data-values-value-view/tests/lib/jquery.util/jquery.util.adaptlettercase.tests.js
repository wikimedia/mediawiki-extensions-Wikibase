/**
 * @license GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

( function () {
	'use strict';

	QUnit.module( 'jquery.util.adaptlettercase' );

	QUnit.test( 'Basic tests', function( assert ) {
		assert.strictEqual(
			$.util.adaptlettercase( 'abc', 'AbC' ),
			'abc',
			'Not adapting any letter-case when omitting \'method\' parameter.'
		);

		assert.strictEqual(
			$.util.adaptlettercase( 'abc', 'AbC', 'all' ),
			'AbC',
			'Adapting the case of all letters when specifying \'all\' as method.'
		);

		assert.strictEqual(
			$.util.adaptlettercase( 'ABC', 'abc', 'first' ),
			'aBC',
			'Adapting the first letter\'s case when specifying \'first\' as method.'
		);

		assert.strictEqual(
			$.util.adaptlettercase( 'AB', 'ab', 'first' ),
			'aB',
			'Adapting the first letter\'s case when specifying \'first\' as method with ' +
				'destination being a a part of source.'
		);

		assert.strictEqual(
			$.util.adaptlettercase( '123', '123', 'all' ),
			'123',
			'No replacement taking place when not passing letters.'
		);

		assert.strictEqual(
			$.util.adaptlettercase( 'abc', '123', 'all' ),
			'abc',
			'Not performing any replacement if strings do not match.'
		);

	} );

}() );
