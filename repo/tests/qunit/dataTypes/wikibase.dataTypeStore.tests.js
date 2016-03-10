/**
 * @since 0.4
 * @license GPL-2.0+
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */

( function( QUnit, dataTypeStore, dataTypes ) {
	'use strict';

	QUnit.module( 'wikibase.dataTypeStore' );

	QUnit.test( 'instance check', function( assert ) {
		assert.expect( 1 );
		assert.ok(
			dataTypeStore instanceof dataTypes.DataTypeStore,
			'wikibase.dataTypeStore is a DataTypeStore.'
		);
	} );

}( QUnit, wikibase.dataTypeStore, dataTypes ) );
