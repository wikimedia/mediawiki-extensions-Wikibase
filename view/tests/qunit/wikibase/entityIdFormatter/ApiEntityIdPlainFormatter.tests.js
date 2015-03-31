( function( $, sinon, QUnit, wb, mw ) {
	'use strict';

	QUnit.module( 'wikibase.entityIdFormatter.ApiEntityIdPlainFormatter' );

	var api = new wb.api.RepoApi( {} );
	api.parseValue = function( dataType, values ) {
		var deferred = $.Deferred();
		if( values[0] === 'Q2' ) {
			deferred.reject();
		} else {
			deferred.resolve(
				{ results: [ { value: { 'entity-type': 'item', 'numeric-id': Number( values[0].substr( 1 ) ) } } ] }
			);
		}
		return deferred.promise();
	};
	api.formatValue = function( value ) {
		var deferred = $.Deferred();
		if( value.value[ 'numeric-id' ] === 3 ) {
			deferred.reject();
		} else {
			deferred.resolve( { result: 'Label' } );
		}
		return deferred.promise();
	};

	function newFormatter() {
		return new wb.entityIdFormatter.ApiEntityIdPlainFormatter( api );
	}

	QUnit.test( 'constructor throws error if no RepoApi is passed', function( assert ) {
		assert.throws( function() {
			return new wb.entityIdFormatter.ApiEntityIdPlainFormatter();
		} );
	} );

	QUnit.test( 'constructor throws error if RepoApi is not instance of RepoApi', function( assert ) {
		assert.throws( function() {
			return new wb.entityIdFormatter.ApiEntityIdPlainFormatter( {} );
		} );
	} );

	QUnit.test( 'format uses parser and formatter', function( assert ) {
		var formatter = newFormatter();
		var done = assert.async();
		formatter.format( 'Q1' ).done( function( res ) {
			assert.equal( res, 'Label' );
			done();
		} );
	} );

	QUnit.test( 'format falls back to the entity id when parsing fails', function( assert ) {
		var formatter = newFormatter();
		var done = assert.async();
		formatter.format( 'Q2' ).done( function( res ) {
			assert.equal( res, 'Q2' );
			done();
		} );
	} );

	QUnit.test( 'format falls back to the entity id when formatting fails', function( assert ) {
		var formatter = newFormatter();
		var done = assert.async();
		formatter.format( 'Q3' ).done( function( res ) {
			assert.equal( res, 'Q3' );
			done();
		} );
	} );

}( jQuery, sinon, QUnit, wikibase, mediaWiki ) );
