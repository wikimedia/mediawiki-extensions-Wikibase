/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( mw, wb ) {
	'use strict';

	QUnit.module( 'wikibase.seedEntityStoreFromMwConfig', QUnit.newWbEnvironment() );

	QUnit.test( 'is a function', function( assert ) {
		assert.equal(
			typeof wb.compileEntityStoreFromMwConfig,
			'function',
			'is a function.'
		);
	} );

	QUnit.test( 'does not do anything if the mw.config parameter is empty', function( assert ) {
		QUnit.expect( 0 );

		mw.config.set( 'wbUsedEntities', '[]' );

		wb.compileEntityStoreFromMwConfig( {
			compile: function() {
				assert.ok( false, 'Triggered compile() although it should not have been called.' );
			}
		} );
	} );

	QUnit.test( 'calls compile() with the content of wbUsedEntitites', function( assert ) {
		mw.config.set(
			'wbUsedEntities',
			'{"P1":{"content":{"id":"P1","type":"property",'
			+ '"descriptions":{"en":{"language":"en","value":"1"}},'
			+ '"labels":{"en":{"language":"en","value":"1"}},"datatype":"string"},'
			+ '"title":"Property:P1"}}'
		);
		wb.compileEntityStoreFromMwConfig( {
			compile: function( objs ) {
				assert.ok(
					objs.P1,
					'Triggered compile() with proper mw.config variable contents.'
				);
			}
		} );
	} );

} )( mediaWiki, wikibase );
