/**
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( mw, wb, $, QUnit ) {
	'use strict';

QUnit.module( 'wikibase.utilities.GuidGenerator', QUnit.newWbEnvironment() );

QUnit.test( 'V4GuidGenerator', function( assert ) {
	var generator = new wb.utilities.V4GuidGenerator();

	assert.equal(
		generator._getRandomHex( 0, 0 ),
		0,
		'getRandomHex(): 0.'
	);

	assert.equal(
		generator._getRandomHex( 65535, 65535 ),
		'ffff',
		'getRandomHex(): 65535.'
	);

	assert.ok(
		/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/.test( generator.newGuid() ),
		'Validated layout of generated GUID.'
	);

} );

}( mediaWiki, wikibase, jQuery, QUnit ) );
