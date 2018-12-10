( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.WikibaseContentLanguages' );

	QUnit.test( 'wikibase.WikibaseContentLanguages.getAllPairs()', function ( assert ) {
		var Map = mw.config.get( 'wgULSLanguages' );
		assert.propEqual(
			( new wb.WikibaseContentLanguages() ).getAllPairs(),
			Map,
			'wb.WikibaseContentLanguages().getAllPairs() returns the language map on a item.'
		);
	} );

}( wikibase ) );
