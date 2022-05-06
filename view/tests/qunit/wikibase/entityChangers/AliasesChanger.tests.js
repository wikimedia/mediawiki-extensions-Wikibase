/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	QUnit.module( 'wikibase.entityChangers.AliasesChanger' );

	var SUBJECT = wikibase.entityChangers.AliasesChanger;

	QUnit.test( 'is a function', function ( assert ) {
		assert.strictEqual(
			typeof SUBJECT,
			'function',
			'is a function.'
		);
	} );

	QUnit.test( 'is a constructor', function ( assert ) {
		assert.true( new SUBJECT() instanceof SUBJECT );
	} );

	QUnit.test( 'setAliases performs correct API call', function ( assert ) {
		var api = {
			setAliases: sinon.spy( function () {
				return $.Deferred().promise();
			} )
		};
		var aliasesChanger = new SUBJECT(
			api,
			{ getAliasesRevision: function () { return 0; } },
			new datamodel.Item( 'Q1' )
		);

		aliasesChanger.setAliases( new datamodel.MultiTerm( 'language', [] ) );

		assert.true( api.setAliases.calledOnce );
	} );

	QUnit.test( 'setAliases correctly handles API response', function ( assert ) {
		var api = {
			setAliases: sinon.spy( function () {
				return $.Deferred().resolve( {
					entity: {}
				} ).promise();
			} )
		};
		var aliasesChanger = new SUBJECT(
			api,
			{
				getAliasesRevision: function () { return 0; },
				setAliasesRevision: function () {}
			},
			new datamodel.Item( 'Q1' )
		);

		return aliasesChanger.setAliases( new datamodel.MultiTerm( 'language', [] ) )
		.done( function ( savedAliases ) {
			assert.true( true, 'setAliases succeeded' );
		} );
	} );

	QUnit.test( 'setAliases correctly handles API failure', function ( assert ) {
		var api = {
			setAliases: sinon.spy( function () {
				return $.Deferred()
					.reject( 'errorCode', { error: { code: 'errorCode' } } )
					.promise();
			} )
		};
		var aliasesChanger = new SUBJECT(
			api,
			{
				getAliasesRevision: function () { return 0; },
				setAliasesRevision: function () {}
			},
			new datamodel.Item( 'Q1' )
		);

		var done = assert.async();

		aliasesChanger.setAliases( new datamodel.MultiTerm( 'language', [] ) )
		.done( function ( savedAliases ) {
			assert.true( false, 'setAliases succeeded' );
		} )
		.fail( function ( error ) {
			assert.true(
				error instanceof wb.api.RepoApiError,
				'setAliases failed with a RepoApiError'
			);

			assert.strictEqual( error.code, 'errorCode' );
		} )
		.always( done );
	} );

	QUnit.test( 'setAliases correctly removes aliases', function ( assert ) {
		var api = {
			setAliases: sinon.spy( function () {
				return $.Deferred().resolve( {
					entity: {}
				} ).promise();
			} )
		};

		var item = new datamodel.Item( 'Q1', new datamodel.Fingerprint(
			null,
			null,
			new datamodel.MultiTermMap( {
				language: new datamodel.MultiTerm( 'language', [ 'alias' ] )
			} )
		) );

		var aliasesChanger = new SUBJECT(
			api,
			{
				getAliasesRevision: function () { return 0; },
				setAliasesRevision: function () {}
			},
			item
		);

		return aliasesChanger.setAliases( new datamodel.MultiTerm( 'language', [] ) )
		.done( function () {
			assert.true( true, 'setAliases succeeded' );

			assert.true(
				item.getFingerprint().getAliasesFor( 'language' ) === null ||
				item.getFingerprint().getAliasesFor( 'language' ).isEmpty(),
				'Verified aliases being empty or removed.'
			);

			sinon.assert.calledWith(
				api.setAliases,
				'Q1',
				0,
				sinon.match( [] ),
				sinon.match( [ 'alias' ] ),
				'language'
			);
		} );
	} );

}( wikibase ) );
