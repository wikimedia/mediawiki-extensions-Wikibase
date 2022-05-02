( function () {
	'use strict';

	var EntityIdHtmlFormatter = require( '../../../../resources/wikibase/entityIdFormatter/EntityIdHtmlFormatter.js' );

	module.exports = {
		all: function ( constructor, getInstance ) {
			this.constructorTests( constructor, getInstance );
			this.formatTests( getInstance );
		},

		constructorTests: function ( constructor, getInstance ) {
			QUnit.test( 'Constructor', function ( assert ) {
				var instance = getInstance();

				assert.true(
					instance instanceof constructor,
					'Instantiated.'
				);

				assert.true(
					instance instanceof EntityIdHtmlFormatter,
					'Instance of EntityIdHtmlFormatter'
				);
			} );
		},

		formatTests: function ( getInstance ) {
			QUnit.test( 'format returns some non-empty string', function ( assert ) {
				var instance = getInstance();
				var done = assert.async();

				instance.format( 'Q1' ).done( function ( res ) {
					assert.strictEqual( typeof res, 'string' );
					assert.notStrictEqual( res, '' );
					done();
				} );
			} );
			QUnit.test( 'format correctly escapes ampersands in the entity id', function ( assert ) {
				var instance = getInstance();
				var done = assert.async();

				instance.format( '&' ).done( function ( res ) {
					assert.strictEqual( res.match( /&($|[^a])/ ), null );
					done();
				} );
			} );
			QUnit.test( 'format correctly escapes HTML in the entity id', function ( assert ) {
				var instance = getInstance();
				var done = assert.async();

				instance.format( '<script>' ).done( function ( res ) {
					assert.strictEqual( $( document.createElement( 'span' ) ).html( res ).find( 'script' ).length, 0 );
					done();
				} );
			} );
		}
	};
}() );
