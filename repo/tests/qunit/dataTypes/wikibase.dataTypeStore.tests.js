/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 */

( function ( QUnit, dataTypeStore, dataTypes ) {
	'use strict';

	QUnit.module( 'wikibase.dataTypeStore' );

	QUnit.test( 'instance check', function ( assert ) {
		assert.expect( 1 );
		assert.ok(
			dataTypeStore instanceof dataTypes.DataTypeStore,
			'wikibase.dataTypeStore is a DataTypeStore.'
		);
	} );

}( QUnit, wikibase.dataTypeStore, wikibase.dataTypes ) );
