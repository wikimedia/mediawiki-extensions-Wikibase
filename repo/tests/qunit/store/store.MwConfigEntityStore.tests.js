/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( mw, wb ) {
	'use strict';

	QUnit.module( 'wikibase.store.MwConfigEntityStore', QUnit.newMwEnvironment() );

	QUnit.test( 'is a function', function( assert ) {
		assert.equal(
			typeof wb.store.MwConfigEntityStore,
			'function',
			'is a function.'
		);
	} );

	QUnit.test( 'Resolves with content from wbUsedEntitites', 1, function( assert ) {
		mw.config.set(
			'wbUsedEntities',
			'{"P1":{"content":{"id":"P1","type":"property",'
			+ '"descriptions":{"en":{"language":"en","value":"1"}},'
			+ '"labels":{"en":{"language":"en","value":"1"}},"datatype":"string"},'
			+ '"title":"Property:P1"}}'
		);

		var store = new wb.store.MwConfigEntityStore( {
			deserialize: function( data ) {
				return data;
			}
		} );

		QUnit.stop();
		store.get( 'P1' ).done( function( entity ) {
			QUnit.start();

			assert.ok( entity );
		} );
	} );

} )( mediaWiki, wikibase );
