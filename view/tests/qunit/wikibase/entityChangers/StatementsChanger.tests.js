/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( sinon, wb, $ ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.StatementsChanger' );

	var SUBJECT = wikibase.entityChangers.StatementsChanger;
	var entity = new wikibase.datamodel.Item( 'Q1' );

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

	QUnit.test( 'remove performs correct API call', function ( assert ) {
		assert.expect( 1 );
		var api = {
			removeClaim: sinon.spy( function () {
				return $.Deferred().promise();
			} )
		};
		var statementsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function () { return 0; } },
			entity
		);

		statementsChanger.remove(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		);

		assert.ok( api.removeClaim.calledOnce );
	} );

	QUnit.test( 'remove correctly handles API response', function ( assert ) {
		assert.expect( 1 );
		var api = {
			removeClaim: sinon.spy( function () {
				return $.Deferred().resolve( {
					claims: [],
					pageinfo: {}
				} ).promise();
			} )
		};
		var statementsChanger = new SUBJECT(
			api,
			{
				getClaimRevision: function () { return 0; },
				setClaimRevision: function () {}
			},
			entity
		);

		return statementsChanger.remove(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		)
		.done( function () {
			assert.ok( true, 'remove succeeded' );
		} );
	} );

	QUnit.test( 'remove correctly handles API failures', function ( assert ) {
		assert.expect( 2 );
		var api = {
			removeClaim: sinon.spy( function () {
				return $.Deferred()
					.reject( 'errorCode', { error: { code: 'errorCode' } } )
					.promise();
			} )
		};
		var statementsChanger = new SUBJECT(
			api,
			{
				getClaimRevision: function () { return 0; },
				setClaimRevision: function () {}
			},
			entity
		);

		var done = assert.async();

		statementsChanger.remove(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		)
		.done( function () {
			assert.ok( false, 'remove should have failed' );
		} )
		.fail( function ( error ) {
			assert.ok(
				error instanceof wb.api.RepoApiError,
				'remove did not fail with a RepoApiError'
			);

			assert.equal( error.code, 'errorCode' );
		} )
		.always( done );
	} );

	QUnit.test( 'remove fires correct hook', function ( assert ) {
		assert.expect( 3 );
		var deferred = $.Deferred();
		var api = {
			removeClaim: sinon.spy( function () {
				return deferred.promise();
			} )
		};
		var fireHook = sinon.spy();
		var statementsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function () { return 0; }, setClaimRevision: function () {} },
			entity,
			new wb.serialization.StatementSerializer(),
			new wb.serialization.StatementDeserializer(),
			fireHook
		);
		var guid = 'Q1$ffbcf247-0c66-4f97-81a0-9d25822104b8';

		statementsChanger.remove(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' ),
				null,
				guid
			) )
		);

		assert.ok( fireHook.notCalled, 'hook should only fire when API call returns' );

		deferred.resolve( { pageinfo: { lastrevid: 2 } } );

		assert.ok( fireHook.calledOnce, 'hook should have fired' );
		assert.ok( fireHook.calledWith( 'wikibase.statement.removed', 'Q1', guid ), 'hook should have correct arguments' );
	} );

	QUnit.test( 'save performs correct API call', function ( assert ) {
		assert.expect( 1 );
		var api = {
			setClaim: sinon.spy( function () {
				return $.Deferred().promise();
			} )
		};
		var statementsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function () { return 0; } },
			entity,
			new wb.serialization.StatementSerializer()
		);

		statementsChanger.save(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		);

		assert.ok( api.setClaim.calledOnce );
	} );

	QUnit.test( 'save correctly handles API response', function ( assert ) {
		assert.expect( 1 );
		var api = {
			setClaim: sinon.spy( function () {
				return $.Deferred().resolve( {
					claim: {
						mainsnak: { snaktype: 'novalue', property: 'P1' },
						rank: 'normal'
					},
					pageinfo: {}
				} ).promise();
			} )
		};
		var statementsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function () { return 0; }, setClaimRevision: function () {} },
			entity,
			new wb.serialization.StatementSerializer(),
			new wb.serialization.StatementDeserializer()
		);

		return statementsChanger.save(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		)
		.done( function ( savedStatement ) {
			assert.ok(
				savedStatement instanceof wb.datamodel.Statement,
				'save did not resolve with a Statement'
			);
		} );
	} );

	QUnit.test( 'save correctly handles API failures', function ( assert ) {
		assert.expect( 2 );
		var api = {
			setClaim: sinon.spy( function () {
				return $.Deferred()
					.reject( 'errorCode', { error: { code: 'errorCode' } } )
					.promise();
			} )
		};
		var statementsChanger = new SUBJECT(
			api,
			{
				getClaimRevision: function () { return 0; },
				setClaimRevision: function () {}
			},
			entity,
			new wb.serialization.StatementSerializer(),
			new wb.serialization.StatementDeserializer()
		);

		var done = assert.async();

		statementsChanger.save(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		)
		.done( function ( savedStatement ) {
			assert.ok( false, 'save should have failed' );
		} )
		.fail( function ( error ) {
			assert.ok(
				error instanceof wb.api.RepoApiError,
				'save failed with a RepoApiError'
			);

			assert.equal( error.code, 'errorCode' );
		} )
		.always( done );
	} );

	QUnit.test( 'save fires correct hook', function ( assert ) {
		assert.expect( 3 );
		var deferred = $.Deferred();
		var api = {
			setClaim: sinon.spy( function () {
				return deferred.promise();
			} )
		};
		var fireHook = sinon.spy();
		var statementsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function () { return 0; }, setClaimRevision: function () {} },
			entity,
			new wb.serialization.StatementSerializer(),
			new wb.serialization.StatementDeserializer(),
			fireHook
		);
		var guid = 'Q1$a69d8233-b677-43e6-a7c6-519f525eab0c';

		statementsChanger.save(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' ),
				null,
				guid
			) )
		);

		assert.ok( fireHook.notCalled, 'hook should only fire when API call returns' );

		deferred.resolve( {
			claim: {
				mainsnak: { snaktype: 'novalue', property: 'P1' },
				id: guid,
				rank: 'normal'
			},
			pageinfo: {}
		} );

		assert.ok( fireHook.calledOnce, 'hook should have fired' );
		assert.ok( fireHook.calledWith( 'wikibase.statement.saved', 'Q1', guid ), 'hook should have correct arguments' );
	} );

}( sinon, wikibase, jQuery ) );
