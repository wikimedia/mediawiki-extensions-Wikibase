/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( $, wb, QUnit ) {
'use strict';

/**
 *  @return {Fingerprint}
 */
function createFingerprint() {
	return new wb.datamodel.Fingerprint(
		new wb.datamodel.TermMap( {
			de: new wb.datamodel.Term( 'de', 'de-label' ),
			en: new wb.datamodel.Term( 'en', 'en-label' ),
			fa: new wb.datamodel.Term( 'fa', 'fa-label' )
		} ),
		new wb.datamodel.TermMap( {
			de: new wb.datamodel.Term( 'de', 'de-description' ),
			en: new wb.datamodel.Term( 'en', 'en-description' ),
			fa: new wb.datamodel.Term( 'fa', 'fa-description' )
		} ),
		new wb.datamodel.MultiTermMap( {
			de: new wb.datamodel.MultiTerm( 'de', [ 'de-alias' ] ),
			en: new wb.datamodel.MultiTerm( 'en', [ 'en-alias' ] ),
			fa: new wb.datamodel.MultiTerm( 'fa', [ 'fa-alias' ] )
		} )
	);
}

/**
 * @param {Object} [options]
 * @return {jQuery}
 */
function createEntitytermsview( options ) {
	options = $.extend( {
		value: createFingerprint(),
		userLanguages: [ 'de', 'en' ],
		entityChangersFactory: {
			getEntityTermsChanger: function() { return 'I am an EntityTermsChanger'; },
		}
	}, options || {} );

	return $( '<div/>' )
		.appendTo( 'body' )
		.addClass( 'test_entitytermsview' )
		.entitytermsview( options );
}

QUnit.module( 'jquery.wikibase.entitytermsview', QUnit.newMwEnvironment( {
	teardown: function() {
		$( '.test_entitytermsview' ).each( function() {
			var $entitytermsview = $( this ),
				entitytermsview = $entitytermsview.data( 'entitytermsview' );

			if ( entitytermsview ) {
				entitytermsview.destroy();
			}

			$entitytermsview.remove();
		} );
	}
} ) );

QUnit.test( 'Create & destroy', function( assert ) {
	assert.expect( 3 );
	assert.throws(
		function() {
			createEntitytermsview( { value: null } );
		},
		'Throwing error when trying to initialize widget without a value.'
	);

	var $entitytermsview = createEntitytermsview(),
		entitytermsview = $entitytermsview.data( 'entitytermsview' );

	assert.ok(
		entitytermsview !== undefined,
		'Created widget.'
	);

	entitytermsview.destroy();

	assert.ok(
		$entitytermsview.data( 'entitytermsview' ) === undefined,
		'Destroyed widget.'
	);
} );

QUnit.test( 'setError()', function( assert ) {
	assert.expect( 1 );
	var $entitytermsview = createEntitytermsview(),
		entitytermsview = $entitytermsview.data( 'entitytermsview' );

	$entitytermsview
	.on( 'entitytermsviewtoggleerror', function( event, error ) {
		assert.ok(
			true,
			'Triggered "toggleerror" event.'
		);
	} );

	entitytermsview.setError();
} );

QUnit.test( 'value()', function( assert ) {
	assert.expect( 2 );
	var $entitytermsview = createEntitytermsview(),
		entitytermsview = $entitytermsview.data( 'entitytermsview' );

	assert.ok(
		entitytermsview.value().equals( createFingerprint() ),
		'Retrieved value.'
	);

	assert.throws(
		function() {
			entitytermsview.value( [] );
		},
		'Throwing error when trying to set a new value.'
	);
} );

}( jQuery, wikibase, QUnit ) );
