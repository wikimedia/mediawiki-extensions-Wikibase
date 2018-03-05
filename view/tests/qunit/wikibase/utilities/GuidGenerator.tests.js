/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.utilities.GuidGenerator' );

	QUnit.test( 'V4GuidGenerator', function ( assert ) {
		assert.expect( 3 );
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

}( wikibase, QUnit ) );
