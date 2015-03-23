( function( $, sinon, QUnit, wb, mw ) {
	'use strict';

	QUnit.module( 'wikibase.entityIdFormatter.SimpleEntityIdPlainFormatter' );

	function newFormatterGetter( entityStoreType ) {
		var entityStore = new wb.store.EntityStore();
		if( entityStoreType === 'empty' ) {
			entityStore.get = function() { return $.Deferred().resolve().promise(); };
		} else if( entityStoreType === 'nolabels' ) {
			entityStore.get = function( entityId ) { return $.Deferred().resolve( new wb.store.FetchedContent( {
				title: $.extend( new mw.Title( 'title' ), {
					namespace: 0,
					title: entityId,
					ext: null,
					fragment: null
				} ),
				content: new wb.datamodel.Item( entityId )
			} ) ); };
		} else if( entityStoreType === 'withlabels' ) {
			entityStore.get = function( entityId ) {
				var item = new wb.datamodel.Item( entityId );
				item.getFingerprint().setLabel( mw.config.get( 'wgUserLanguage' ), new wb.datamodel.Term( mw.config.get( 'wgUserLanguage' ), 'Label' ) );
				return $.Deferred().resolve( new wb.store.FetchedContent( {
					title: $.extend( new mw.Title( 'title' ), {
						namespace: 0,
						title: entityId,
						ext: null,
						fragment: null
					} ),
					content: item
				} ) );
			};
		}
		return function() {
			return new wb.entityIdFormatter.SimpleEntityIdPlainFormatter( entityStore );
		};
	}

	QUnit.test( 'constructor throws error if no entity store is passed', function( assert ) {
		assert.throws( function() {
			return new wb.entityIdFormatter.SimpleEntityIdPlainFormatter();
		} );
	} );

	QUnit.test( 'constructor throws error if entity store is not instance of EntityStore', function( assert ) {
		assert.throws( function() {
			return new wb.entityIdFormatter.SimpleEntityIdPlainFormatter( {} );
		} );
	} );

	QUnit.test( 'format uses label when it exists', function( assert ) {
		var formatter = newFormatterGetter( 'withlabels' )();
		var done = assert.async();
		formatter.format( 'Q1' ).done( function( res ) {
			assert.equal( res, 'Label' );
			done();
		} );
	} );

	QUnit.test( 'format falls back to the entity id when no label exists', function( assert ) {
		var formatter = newFormatterGetter( 'nolabels' )();
		var done = assert.async();
		formatter.format( 'Q1' ).done( function( res ) {
			assert.equal( res, 'Q1' );
			done();
		} );
	} );

	QUnit.test( 'format falls back to the entity id when entity does not exist', function( assert ) {
		var formatter = newFormatterGetter( 'empty' )();
		var done = assert.async();
		formatter.format( 'Q1' ).done( function( res ) {
			assert.equal( res, 'Q1' );
			done();
		} );
	} );

}( jQuery, sinon, QUnit, wikibase, mediaWiki ) );
