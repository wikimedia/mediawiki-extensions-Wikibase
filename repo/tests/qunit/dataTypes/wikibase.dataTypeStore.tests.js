/**
 * @since 0.4
 * @licence GNU GPL v2+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( QUnit, dataTypeStore, dataTypes ) {
	'use strict';

	QUnit.module( 'wikibase.dataTypeStore' );

	QUnit.test( 'instance check', function( assert ) {
		assert.ok(
			dataTypeStore instanceof dataTypes.DataTypeStore,
			'wikibase.dataTypeStore is a DataTypeStore.'
		);
	} );

}( QUnit, wikibase.dataTypeStore, dataTypes ) );
