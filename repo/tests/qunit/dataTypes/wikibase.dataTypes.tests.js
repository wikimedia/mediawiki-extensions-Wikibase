/**
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( QUnit, dataTypeStore, dataTypes ) {
	'use strict';

	QUnit.module( 'wikibase.dataTypes' );

	QUnit.test( 'instance check', function( assert ) {
		assert.ok(
			dataTypeStore instanceof dataTypes.DataTypeStore,
			'wikibase.dataTypes is a data type store.'
		);
	} );

}( QUnit, wikibase.dataTypes, dataTypes ) );
