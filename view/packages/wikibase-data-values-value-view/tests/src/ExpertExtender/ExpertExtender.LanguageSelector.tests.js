/**
 * @license GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
/* jshint nonew: false */
( function( $, ExpertExtender, testExpertExtenderExtension, sinon, QUnit ) {
	'use strict';

	const LanguageSelector = require( '../../../src/ExpertExtender/ExpertExtender.LanguageSelector.js' );

	QUnit.module( 'LanguageSelector' );

	const messageProvider = {
		getMessage: function( key, params ) {
			return params && params.length > 0
				? params.join( ' ' )
				: key;
		}
	};

	testExpertExtenderExtension.all(
		LanguageSelector,
		() => new LanguageSelector(
			{
				getAll: () => null
			},
			messageProvider,
			( () => { } )
		)
	);

	/**
	 * @param {Object} languageMap - maps language codes to language names
	 * @return {util.ContentLanguages}
	 */
	function newContentLanguagesFromLanguageMap( languageMap ) {
		return {
			getAll: () => Object.keys( languageMap ),
			getName: ( code ) => languageMap[code] || null
		};
	}

	QUnit.test( 'initial draw works when the upstream value is null', ( assert ) => {
		const languageSelector = new LanguageSelector(
			newContentLanguagesFromLanguageMap( {
				en: 'en label'
			} ),
			messageProvider,
			( () => null )
		);
		const $extender = $( '<div />' );

		languageSelector.init( $extender );

		if ( languageSelector.onInitialShow ) {
			languageSelector.onInitialShow();
		}

		if ( languageSelector.draw ) {
			languageSelector.draw();
		}

		assert.ok( true );
	} );

	QUnit.test( 'value does not change if upstream value changes', ( assert ) => {
		let upstreamValue = 'en';
		const languageSelector = new LanguageSelector(
			newContentLanguagesFromLanguageMap( {
				de: 'de label',
				en: 'en label'
			} ),
			messageProvider,
			( () => upstreamValue )
		);
		const $extender = $( '<div />' );

		languageSelector.init( $extender );

		if ( languageSelector.onInitialShow ) {
			languageSelector.onInitialShow();
		}

		if ( languageSelector.draw ) {
			languageSelector.draw();
		}

		assert.strictEqual( languageSelector.getValue(), 'en' );

		upstreamValue = 'de';

		if ( languageSelector.draw ) {
			languageSelector.draw();
		}

		assert.strictEqual( languageSelector.getValue(), 'en' );
	} );

	QUnit.test( 'returns correct value after initialization', ( assert ) => {
		const languageSelector = new LanguageSelector(
			newContentLanguagesFromLanguageMap( {
				en: 'en label'
			} ),
			messageProvider,
			( () => 'en' )
		);
		const $extender = $( '<div />' );

		languageSelector.init( $extender );

		if ( languageSelector.onInitialShow ) {
			languageSelector.onInitialShow();
		}

		if ( languageSelector.draw ) {
			languageSelector.draw();
		}

		assert.strictEqual( languageSelector.getValue(), 'en' );
		assert.strictEqual( languageSelector.$selector.val(), 'en label en' );
	} );

	QUnit.test( 'returns correct value after changing it', ( assert ) => {
		const languageSelector = new LanguageSelector(
			newContentLanguagesFromLanguageMap( {
				en: 'en label',
				fr: 'fr label'
			} ),
			messageProvider,
			( () => 'en' )
		);
		const $extender = $( '<div />' );

		languageSelector.init( $extender );

		if ( languageSelector.onInitialShow ) {
			languageSelector.onInitialShow();
		}

		if ( languageSelector.draw ) {
			languageSelector.draw();
		}

		languageSelector.$selector.val( 'fr' ).trigger( 'keydown' );

		assert.strictEqual( languageSelector.getValue(), 'fr' );
		assert.strictEqual( languageSelector.$selector.val(), 'fr' );
	} );

	QUnit.test( 'returns correct value after initialization for value without label in ContentLanguages', ( assert ) => {
		const languageSelector = new LanguageSelector(
			newContentLanguagesFromLanguageMap( {
				en: 'en label',
				ar: null
			} ),
			messageProvider,
			( () => 'ar' )
		);
		const $extender = $( '<div />' );

		languageSelector.init( $extender );

		if ( languageSelector.onInitialShow ) {
			languageSelector.onInitialShow();
		}

		if ( languageSelector.draw ) {
			languageSelector.draw();
		}

		assert.strictEqual( languageSelector.getValue(), 'ar' );
		assert.strictEqual( languageSelector.$selector.val(), 'ar' );
	} );

	QUnit.test( 'returns correct value after changing it to a value without label in ContentLanguages', ( assert ) => {
		const languageSelector = new LanguageSelector(
			newContentLanguagesFromLanguageMap( {
				en: 'en label',
				fr: null
			} ),
			messageProvider,
			( () => 'en' )
		);
		const $extender = $( '<div />' );

		languageSelector.init( $extender );

		if ( languageSelector.onInitialShow ) {
			languageSelector.onInitialShow();
		}

		if ( languageSelector.draw ) {
			languageSelector.draw();
		}

		languageSelector.$selector.val( 'fr' ).trigger( 'keydown' );

		assert.strictEqual( languageSelector.getValue(), 'fr' );
		assert.strictEqual( languageSelector.$selector.val(), 'fr' );
	} );

} )(
	jQuery,
	jQuery.valueview.ExpertExtender,
	jQuery.valueview.tests.testExpertExtenderExtension,
	sinon,
	QUnit
);
