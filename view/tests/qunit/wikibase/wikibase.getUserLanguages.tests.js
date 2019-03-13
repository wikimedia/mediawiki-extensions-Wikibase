( function ( wb, sinon ) {
	'use strict';

	QUnit.module( 'wikibase.getUserLanguages' );

	QUnit.test( 'getUserLanguages something', function ( assert ) {
		// var configStub = sinon.stub( mw.config, 'get' );

		assert.ok( wb.getUserLanguages() );
	} );

}( wikibase, sinon ) );
