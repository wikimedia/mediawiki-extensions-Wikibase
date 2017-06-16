( function ( $, QUnit, wb ) {
	'use strict';

	wb.entityIdFormatter.testEntityIdHtmlFormatter = {
		all: function ( constructor, getInstance ) {
			this.constructorTests( constructor, getInstance );
			this.formatTests( getInstance );
		},

		constructorTests: function ( constructor, getInstance ) {
			QUnit.test( 'Constructor', function ( assert ) {
				assert.expect( 2 );
				var instance = getInstance();

				assert.ok(
					instance instanceof constructor,
					'Instantiated.'
				);

				assert.ok(
					instance instanceof wb.entityIdFormatter.EntityIdHtmlFormatter,
					'Instance of EntityIdHtmlFormatter'
				);
			} );
		},

		formatTests: function ( getInstance ) {
			QUnit.test( 'format returns some non-empty string', function ( assert ) {
				assert.expect( 2 );
				var instance = getInstance();
				var done = assert.async();

				instance.format( 'Q1' ).done( function ( res ) {
					assert.equal( typeof res, 'string' );
					assert.notEqual( res, '' );
					done();
				} );
			} );
			QUnit.test( 'format correctly escapes ampersands in the entity id', function ( assert ) {
				assert.expect( 1 );
				var instance = getInstance();
				var done = assert.async();

				instance.format( '&' ).done( function ( res ) {
					assert.equal( res.match( /&($|[^a])/ ), null );
					done();
				} );
			} );
			QUnit.test( 'format correctly escapes HTML in the entity id', function ( assert ) {
				assert.expect( 1 );
				var instance = getInstance();
				var done = assert.async();

				instance.format( '<script>' ).done( function ( res ) {
					assert.equal( $( document.createElement( 'span' ) ).html( res ).find( 'script' ).length, 0 );
					done();
				} );
			} );
		}
	};
}( jQuery, QUnit, wikibase ) );
