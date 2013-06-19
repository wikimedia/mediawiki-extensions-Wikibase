/**
 * @since 0.4
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( QUnit, wbDataTypes ) {
	'use strict';

	QUnit.module( 'wikibase.dataTypes' );

	QUnit.test( 'instance check', function( assert ) {
		assert.ok(
			wbDataTypes === dataTypes, // See TODO in wbikibase.dataTypes.js regarding this oddity.
			'wb.DataTypes is data types factory'
		);
	} );

}( QUnit, wikibase.dataTypes ) );
