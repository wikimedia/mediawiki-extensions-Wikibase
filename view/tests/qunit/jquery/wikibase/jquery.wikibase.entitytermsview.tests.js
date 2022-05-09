/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 */
( function () {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	/**
	 *  @return {datamodel.Fingerprint}
	 */
	function createFingerprint() {
		return new datamodel.Fingerprint(
			new datamodel.TermMap( {
				de: new datamodel.Term( 'de', 'de-label' ),
				en: new datamodel.Term( 'en', 'en-label' ),
				fa: new datamodel.Term( 'fa', 'fa-label' )
			} ),
			new datamodel.TermMap( {
				de: new datamodel.Term( 'de', 'de-description' ),
				en: new datamodel.Term( 'en', 'en-description' ),
				fa: new datamodel.Term( 'fa', 'fa-description' )
			} ),
			new datamodel.MultiTermMap( {
				de: new datamodel.MultiTerm( 'de', [ 'de-alias' ] ),
				en: new datamodel.MultiTerm( 'en', [ 'en-alias' ] ),
				fa: new datamodel.MultiTerm( 'fa', [ 'fa-alias' ] )
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
			userLanguages: [ 'de', 'en' ]
		}, options || {} );

		return $( '<div>' )
			.appendTo( document.body )
			.addClass( 'test_entitytermsview' )
			.entitytermsview( options );
	}

	QUnit.module( 'jquery.wikibase.entitytermsview', QUnit.newMwEnvironment( {
		afterEach: function () {
			$( '.test_entitytermsview' ).each( function () {
				var $entitytermsview = $( this ),
					entitytermsview = $entitytermsview.data( 'entitytermsview' );

				if ( entitytermsview ) {
					entitytermsview.destroy();
				}

				$entitytermsview.remove();
			} );
		}
	} ) );

	QUnit.skip( 'Create & destroy', function ( assert ) {
		assert.throws(
			function () {
				createEntitytermsview( { value: null } );
			},
			'Throwing error when trying to initialize widget without a value.'
		);

		var $entitytermsview = createEntitytermsview(),
			entitytermsview = $entitytermsview.data( 'entitytermsview' );

		assert.true(
			entitytermsview !== undefined,
			'Created widget.'
		);

		entitytermsview.destroy();

		assert.true(
			$entitytermsview.data( 'entitytermsview' ) === undefined,
			'Destroyed widget.'
		);
	} );

	QUnit.test( 'setError()', function ( assert ) {
		var $entitytermsview = createEntitytermsview(),
			entitytermsview = $entitytermsview.data( 'entitytermsview' );

		$entitytermsview
		.on( 'entitytermsviewtoggleerror', function ( event, error ) {
			assert.true(
				true,
				'Triggered "toggleerror" event.'
			);
		} );

		entitytermsview.setError();
	} );

	QUnit.test( 'value()', function ( assert ) {
		var $entitytermsview = createEntitytermsview(),
			entitytermsview = $entitytermsview.data( 'entitytermsview' );

		assert.true(
			entitytermsview.value().equals( createFingerprint() ),
			'Retrieved value.'
		);

		assert.throws(
			function () {
				entitytermsview.value( [] );
			},
			'Throwing error when trying to set a new value.'
		);
	} );

}() );
