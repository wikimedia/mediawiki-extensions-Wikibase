( function ( $, sinon, QUnit, wb, mw ) {
	'use strict';

	QUnit.module( 'wikibase.entityIdFormatter.DataValueBasedEntityIdHtmlFormatter' );

	function newFormatterGetter( repoType ) {
		var parser, formatter;
		if ( repoType === 'parsefail' ) {
			parser = {
				parse: function () { return $.Deferred().reject( 'parse error' ).promise(); }
			};
			formatter = null;
		} else if ( repoType === 'formatfail' ) {
			parser = {
				parse: function () { return $.Deferred().resolve( 'parsed DataValue' ).promise(); }
			};
			formatter = {
				format: function () { return $.Deferred().reject( 'format error' ).promise(); }
			};
		} else if ( repoType === 'success' ) {
			parser = {
				parse: function () { return $.Deferred().resolve( 'parsed DataValue' ).promise(); }
			};
			formatter = {
				format: function () { return $.Deferred().resolve( 'formatted value' ).promise(); }
			};
		}
		return function () {
			return new wb.entityIdFormatter.DataValueBasedEntityIdHtmlFormatter( parser, formatter );
		};
	}

	QUnit.test( 'format returns formatter return value', function ( assert ) {
		assert.expect( 1 );
		var formatter = newFormatterGetter( 'success' )();
		var done = assert.async();
		formatter.format( 'Q1' ).done( function ( res ) {
			assert.equal( res, 'formatted value' );
			done();
		} );
	} );

	QUnit.test( 'format falls back to plain id on parse error', function ( assert ) {
		assert.expect( 1 );
		var formatter = newFormatterGetter( 'parsefail' )();
		var done = assert.async();
		formatter.format( 'Q1' ).done( function ( res ) {
			assert.equal( res, 'Q1' );
			done();
		} );
	} );

	QUnit.test( 'format falls back to plain id on formatter error', function ( assert ) {
		assert.expect( 1 );
		var formatter = newFormatterGetter( 'formatfail' )();
		var done = assert.async();
		formatter.format( 'Q1' ).done( function ( res ) {
			assert.equal( res, 'Q1' );
			done();
		} );
	} );

}( jQuery, sinon, QUnit, wikibase, mediaWiki ) );
