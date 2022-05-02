/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function ( wb, QUnit ) {
	'use strict';

	QUnit.module( 'wikibase.utilities.ClaimGuidGenerator' );

	QUnit.test( 'Validate GUID layout', function ( assert ) {
		var generator = new wb.utilities.ClaimGuidGenerator( 'q79' );

		assert.true(
			/q79\$[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/.test(
				generator.newGuid()
			),
			'Validated layout of generated GUID.'
		);

	} );

}( wikibase, QUnit ) );
