/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( $, mw, wb ) {
  'use strict';

  QUnit.module( 'wikibase.seedEntityStoreFromMwConfig', QUnit.newWbEnvironment( {
  } ) );

  QUnit.test( 'is a function', function( assert ) {
    assert.equal(typeof wb.seedEntityStoreFromMwConfig, 'function', 'is a function' );
  } );

	QUnit.test( 'does not do anything if the mw config parameter is empty', function( assert ) {
		QUnit.expect( 0 );

		mw.config.set( 'wbUsedEntities', '[]' );
		wb.seedEntityStoreFromMwConfig( {
			seed: function() {
				assert.ok( false, 'seed should not have been called' );
			}
		} );
	} );

	QUnit.test( 'calls seed with the content of wbUsedEntitites', function( assert ) {
		mw.config.set( 'wbUsedEntities', '{"P1":{"content":{"id":"P1","type":"property",' +
			'"descriptions":{"en":{"language":"en","value":"1"}},' +
			'"labels":{"en":{"language":"en","value":"1"}},"datatype":"string"},' +
			'"title":"Property:P1"}}' );
		wb.seedEntityStoreFromMwConfig( {
			seed: function( objs ) {
				assert.ok( objs.P1, 'seed should have been called with the right objects given' );
			}
		} );
	} );

} )( jQuery, mediaWiki, wikibase );
