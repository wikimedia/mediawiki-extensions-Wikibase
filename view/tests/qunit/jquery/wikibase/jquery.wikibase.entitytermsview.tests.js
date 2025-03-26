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
		options = Object.assign( {
			value: createFingerprint(),
			userLanguages: [ 'de', 'en' ]
		}, options || {} );

		return $( '<div>' )
			.appendTo( document.body )
			.addClass( 'test_entitytermsview' )
			.entitytermsview( options );
	}

	function stubOptions( cookie, option ) {
		const optionKey = 'wikibase-entitytermsview-showEntitytermslistview';
		const userNameStub = sinon.stub( mw.config, 'get' );
		userNameStub.withArgs( 'wgUserName' ).returns( null );
		const cookieStub = sinon.stub( mw.cookie, 'get' );
		cookieStub.withArgs( optionKey ).returns( cookie );
		const optionsStub = sinon.stub( mw.user.options, 'get' );
		optionsStub.withArgs( optionKey ).returns( option );
		return [ userNameStub, cookieStub, optionsStub ];
	}

	function restoreStubs( stubs ) {
		stubs.forEach( ( stub ) => {
			stub.restore();
		} );
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

	QUnit.skip( 'Create & destroy', ( assert ) => {
		assert.throws(
			() => {
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

	QUnit.test( 'Initial state of listlanguageview is visible by default for logged-out users',
		( assert ) => {
			const stubs = stubOptions( null, null );

			var $entitytermsview = createEntitytermsview();
			var $entitytermsforlanguagelistview = $entitytermsview.find( '.wikibase-entitytermsview-entitytermsforlanguagelistview' );
			var display = $entitytermsforlanguagelistview.css( 'display' );

			assert.false( mw.user.isNamed(), "User should not be set (got '" + mw.user.getName() + '")' );
			assert.true( display !== 'none', 'Element should be visible - got display=' + display );

			restoreStubs( stubs );
		}
	);

	QUnit.test( 'Initial state of listlanguageview is visible if cookie is set and option is null', ( assert ) => {
		const stubs = stubOptions( 'true', null );

		var $entitytermsview = createEntitytermsview();
		var $entitytermsforlanguagelistview = $entitytermsview.find( '.wikibase-entitytermsview-entitytermsforlanguagelistview' );
		var display = $entitytermsforlanguagelistview.css( 'display' );

		assert.true( display !== 'none', 'Element should be visible - got display=' + display );

		restoreStubs( stubs );
	} );

	QUnit.test( 'Initial state of listlanguageview respects option above cookie', ( assert ) => {
		const stubs = stubOptions( 'true', '0' );

		var $entitytermsview = createEntitytermsview();
		var $entitytermsforlanguagelistview = $entitytermsview.find( '.wikibase-entitytermsview-entitytermsforlanguagelistview' );
		var display = $entitytermsforlanguagelistview.css( 'display' );

		assert.true( display === 'none', 'Element should not be visible - got display=' + display );

		restoreStubs( stubs );
	} );

	QUnit.test( 'Initial state of listlanguageview is not visible if cookie is not set and option is 0', ( assert ) => {
		const stubs = stubOptions( null, '0' );

		var $entitytermsview = createEntitytermsview();
		var $entitytermsforlanguagelistview = $entitytermsview.find( '.wikibase-entitytermsview-entitytermsforlanguagelistview' );
		var display = $entitytermsforlanguagelistview.css( 'display' );

		assert.true( display === 'none', 'Element should not be visible - got display=' + display );

		restoreStubs( stubs );
	} );

	QUnit.test( 'Initial state of listlanguageview is not visible if cookie is set to false', ( assert ) => {
		const stubs = stubOptions( 'false', null );

		var $entitytermsview = createEntitytermsview();
		var $entitytermsforlanguagelistview = $entitytermsview.find( '.wikibase-entitytermsview-entitytermsforlanguagelistview' );
		var display = $entitytermsforlanguagelistview.css( 'display' );

		assert.true( display === 'none', 'Element should not be visible - got display=' + display );

		restoreStubs( stubs );
	} );

	QUnit.test( 'Initial state of listlanguageview is visible if option is set', ( assert ) => {
		const stubs = stubOptions( null, '1' );

		var $entitytermsview = createEntitytermsview();
		var $entitytermsforlanguagelistview = $entitytermsview.find( '.wikibase-entitytermsforlanguagelistview' );

		// We want to see if the element is visible on the page, and not rely on model state here.
		// eslint-disable-next-line no-jquery/no-sizzle
		assert.true( $( $entitytermsforlanguagelistview ).is( ':visible' ) );

		restoreStubs( stubs );
	} );

	QUnit.test( 'setError()', ( assert ) => {
		var $entitytermsview = createEntitytermsview(),
			entitytermsview = $entitytermsview.data( 'entitytermsview' );

		$entitytermsview
		.on( 'entitytermsviewtoggleerror', ( event, error ) => {
			assert.true(
				true,
				'Triggered "toggleerror" event.'
			);
		} );

		entitytermsview.setError();
	} );

	QUnit.test( 'value()', ( assert ) => {
		var $entitytermsview = createEntitytermsview(),
			entitytermsview = $entitytermsview.data( 'entitytermsview' );

		assert.true(
			entitytermsview.value().equals( createFingerprint() ),
			'Retrieved value.'
		);

		assert.throws(
			() => {
				entitytermsview.value( [] );
			},
			'Throwing error when trying to set a new value.'
		);
	} );

}() );
