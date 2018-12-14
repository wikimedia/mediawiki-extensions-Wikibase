( function ( wb, sinon ) {
	'use strict';

	QUnit.module( 'wikibase.WikibaseContentLanguages' );

	QUnit.test( 'wikibase.WikibaseContentLanguages.getAllPairs()', function ( assert ) {
		var configStub = sinon.stub( mw.config, 'get' ),
			languages = {
				en: 'English'
			};
		configStub.returns( languages );

		var result = ( new wb.WikibaseContentLanguages() ).getAllPairs();
		assert.propEqual(
			result,
			languages,
			'wb.WikibaseContentLanguages().getAllPairs() returns the language map on a item.'
		);

		assert.notStrictEqual( result, languages );

		configStub.restore();
	} );

}( wikibase, sinon ) );
