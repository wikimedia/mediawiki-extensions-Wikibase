/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( wb, QUnit ) {
	'use strict';

QUnit.module( 'wikibase.utilities.ClaimGuidGenerator' );

QUnit.test( 'Validate GUID layout', function( assert ) {
	var generator = new wb.utilities.ClaimGuidGenerator();

	assert.ok(
		/q79\$[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/.test(
			generator.newGuid( 'q79' )
		),
		'Validated layout of generated GUID.'
	);

} );

}( wikibase, QUnit ) );
