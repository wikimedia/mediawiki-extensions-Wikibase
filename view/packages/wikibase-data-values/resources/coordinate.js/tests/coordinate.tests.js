/**
 * @since 0.1
 * @file
 * @ingroup coordinate.js
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( QUnit, $, coordinate ) {
	'use strict';

	var precisions = {
		0: String.fromCharCode( 0x00B1 ) + '1e-9°',
		1: 1,
		1.00000001: 1,
		0.016666666666666666: 1 / 60,
		2.7777777777777776e-7: 1 / 3600000,
		2: String.fromCharCode( 0x00B1 ) + '2°',
		1.0000000001e-10: String.fromCharCode( 0x00B1 ) + '1e-9°',
		1.0000001: String.fromCharCode( 0x00B1 ) + '1.0000001°',
		1.1: String.fromCharCode( 0x00B1 ) + '1.1°'
	};

	QUnit.module( 'coordinate.js' );

	QUnit.test( 'precisionText()', function( assert ) {

		$.each( precisions, function( precision, expected ) {
			var precisionText = coordinate.precisionText( precision );

			// Look up precision text:
			if( typeof expected === 'number' ) {

				$.each( coordinate.settings.precisionTexts, function( i, textDefinition ) {
					if( textDefinition.precision === expected ) {

						assert.equal(
							precisionText,
							textDefinition.text,
							'Precision text for \'' + precision + '\' results in text \'' + precisionText + '\'.'
						);

						return false;
					}
				} );

			} else {

				assert.equal(
					precisionText,
					expected,
					'Precision text for \'' + precision + '\' results in text \'' + precisionText + '\'.'
				);

			}

		} );

	} );

}( QUnit, jQuery, coordinate ) );
