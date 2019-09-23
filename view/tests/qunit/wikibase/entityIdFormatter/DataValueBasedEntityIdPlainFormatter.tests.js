( function () {
	'use strict';

	var DataValueBasedEntityIdHtmlFormatter = require( '../../../../resources/wikibase/entityIdFormatter/DataValueBasedEntityIdHtmlFormatter.js' );

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
			return new DataValueBasedEntityIdHtmlFormatter( parser, formatter );
		};
	}

	QUnit.test( 'format returns formatter return value', function ( assert ) {
		var formatter = newFormatterGetter( 'success' )();
		var done = assert.async();
		formatter.format( 'Q1' ).done( function ( res ) {
			assert.strictEqual( res, 'formatted value' );
			done();
		} );
	} );

	QUnit.test( 'format falls back to plain id on parse error', function ( assert ) {
		var formatter = newFormatterGetter( 'parsefail' )();
		var done = assert.async();
		formatter.format( 'Q1' ).done( function ( res ) {
			assert.strictEqual( res, 'Q1' );
			done();
		} );
	} );

	QUnit.test( 'format falls back to plain id on formatter error', function ( assert ) {
		var formatter = newFormatterGetter( 'formatfail' )();
		var done = assert.async();
		formatter.format( 'Q1' ).done( function ( res ) {
			assert.strictEqual( res, 'Q1' );
			done();
		} );
	} );

}() );
