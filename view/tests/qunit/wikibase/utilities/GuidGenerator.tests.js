/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb, QUnit ) {
	'use strict';

	var V4GuidGenerator = require( '../../../../resources/wikibase/utilities/wikibase.utilities.GuidGenerator.js' );

	QUnit.module( 'wikibase.utilities.GuidGenerator' );

	QUnit.test( 'V4GuidGenerator', function ( assert ) {
		var generator = new V4GuidGenerator();

		assert.strictEqual(
			generator._getRandomHex( 0, 0 ),
			'0',
			'getRandomHex(): 0.'
		);

		assert.strictEqual(
			generator._getRandomHex( 65535, 65535 ),
			'ffff',
			'getRandomHex(): 65535.'
		);

		assert.true(
			/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/.test( generator.newGuid() ),
			'Validated layout of generated GUID.'
		);

	} );

}( wikibase, QUnit ) );
