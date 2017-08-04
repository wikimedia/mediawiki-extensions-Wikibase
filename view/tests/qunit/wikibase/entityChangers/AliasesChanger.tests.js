/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( sinon, wb, $ ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.AliasesChanger' );

	var SUBJECT = wikibase.entityChangers.AliasesChanger;

	QUnit.test( 'is a function', function ( assert ) {
		assert.expect( 1 );
		assert.equal(
			typeof SUBJECT,
			'function',
			'is a function.'
		);
	} );

	QUnit.test( 'is a constructor', function ( assert ) {
		assert.expect( 1 );
		assert.ok( new SUBJECT() instanceof SUBJECT );
	} );

	QUnit.test( 'setAliases performs correct API call', function ( assert ) {
		assert.expect( 1 );
		var api = {
			setAliases: sinon.spy( function () {
				return $.Deferred().promise();
			} )
		};
		var aliasesChanger = new SUBJECT(
			api,
			{ getAliasesRevision: function () { return 0; } },
			new wb.datamodel.Item( 'Q1' )
		);

		aliasesChanger.setAliases( new wb.datamodel.MultiTerm( 'language', [] ) );

		assert.ok( api.setAliases.calledOnce );
	} );

	QUnit.test( 'setAliases correctly handles API response', function ( assert ) {
		assert.expect( 1 );
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
			new wb.datamodel.Item( 'Q1' )
		);

		return aliasesChanger.setAliases( new wb.datamodel.MultiTerm( 'language', [] ) )
		.done( function ( savedAliases ) {
			assert.ok( true, 'setAliases succeeded' );
		} );
	} );

	QUnit.test( 'setAliases correctly handles API failure', function ( assert ) {
		assert.expect( 2 );
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
			new wb.datamodel.Item( 'Q1' )
		);

		var done = assert.async();

		aliasesChanger.setAliases( new wb.datamodel.MultiTerm( 'language', [] ) )
		.done( function ( savedAliases ) {
			assert.ok( false, 'setAliases succeeded' );
		} )
		.fail( function ( error ) {
			assert.ok(
				error instanceof wb.api.RepoApiError,
				'setAliases failed with a RepoApiError'
			);

			assert.equal( error.code, 'errorCode' );
		} )
		.always( done );
	} );

	QUnit.test( 'setAliases correctly removes aliases', function ( assert ) {
		assert.expect( 3 );
		var api = {
			setAliases: sinon.spy( function () {
				return $.Deferred().resolve( {
					entity: {}
				} ).promise();
			} )
		};

		var item = new wb.datamodel.Item( 'Q1', new wb.datamodel.Fingerprint(
			null,
			null,
			new wb.datamodel.MultiTermMap( {
				language: new wb.datamodel.MultiTerm( 'language', [ 'alias' ] )
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

		return aliasesChanger.setAliases( new wb.datamodel.MultiTerm( 'language', [] ) )
		.done( function () {
			assert.ok( true, 'setAliases succeeded' );

			assert.ok(
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

}( sinon, wikibase, jQuery ) );
