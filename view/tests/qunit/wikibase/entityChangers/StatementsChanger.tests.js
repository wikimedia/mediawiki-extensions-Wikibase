/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' ),
		serialization = require( 'wikibase.serialization' );

	QUnit.module( 'wikibase.entityChangers.StatementsChanger' );

	var SUBJECT = wikibase.entityChangers.StatementsChanger;
	var statementsChangerState = new wikibase.entityChangers.StatementsChangerState(
		'Q1',
		new datamodel.StatementGroupSet()
	);

	function newNoValueSnakStatement( guid ) {
		return new datamodel.Statement( new datamodel.Claim(
			new datamodel.PropertyNoValueSnak( 'P1' ), null, guid
		) );
	}

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

	QUnit.test( 'remove performs correct API call', function ( assert ) {
		var api = {
			removeClaim: sinon.spy( function () {
				return $.Deferred().promise();
			} )
		};
		var statementsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function () { return 0; } },
			statementsChangerState
		);

		statementsChanger.remove( newNoValueSnakStatement() );

		assert.true( api.removeClaim.calledOnce );
	} );

	QUnit.test( 'remove correctly handles API response', function ( assert ) {
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
			statementsChangerState
		);

		return statementsChanger.remove( newNoValueSnakStatement() )
		.done( function () {
			assert.true( true, 'remove succeeded' );
		} );
	} );

	QUnit.test( 'remove correctly handles API failures', function ( assert ) {
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
			statementsChangerState
		);

		var done = assert.async();

		statementsChanger.remove( newNoValueSnakStatement() )
		.done( function () {
			assert.true( false, 'remove should have failed' );
		} )
		.fail( function ( error ) {
			assert.true(
				error instanceof wb.api.RepoApiError,
				'remove did not fail with a RepoApiError'
			);

			assert.strictEqual( error.code, 'errorCode' );
		} )
		.always( done );
	} );

	QUnit.test( 'remove fires correct hook', function ( assert ) {
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
			statementsChangerState,
			new serialization.StatementSerializer(),
			new serialization.StatementDeserializer(),
			fireHook
		);
		var guid = 'Q1$ffbcf247-0c66-4f97-81a0-9d25822104b8';

		statementsChanger.remove( newNoValueSnakStatement( guid ) );

		assert.true( fireHook.notCalled, 'hook should only fire when API call returns' );

		deferred.resolve( { pageinfo: { lastrevid: 2 } } );

		assert.true( fireHook.calledOnce, 'hook should have fired' );
		assert.true( fireHook.calledWith( 'wikibase.statement.removed', 'Q1', guid ), 'hook should have correct arguments' );
	} );

	QUnit.test( 'remove properly updates StatementsChangerState', function ( assert ) {
		var deferred = $.Deferred();
		var api = {
			removeClaim: function () {
				return deferred.promise();
			}
		};
		var statement1 = newNoValueSnakStatement( 'apple' );
		var statement2 = newNoValueSnakStatement( 'pie' );
		var statementsChangerState1 = new wb.entityChangers.StatementsChangerState(
			'Q1',
			new datamodel.StatementGroupSet( [
				new datamodel.StatementGroup(
					'P1',
					new datamodel.StatementList( [ statement1, statement2 ] )
				)
			] )
		);
		var statementsChangerStatements = statementsChangerState1.getStatements();
		var statementsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function () { return 0; }, setClaimRevision: function () {} },
			statementsChangerState1
		);

		assert.strictEqual(
			statementsChangerStatements.getItemByKey( 'P1' ).getItemContainer().length,
			2
		);
		statementsChanger.remove( statement1 );
		deferred.resolve( { pageinfo: { lastrevid: 12 } } );

		var actualStatements = statementsChangerStatements.getItemByKey( 'P1' ).getItemContainer().toArray();
		assert.strictEqual( actualStatements.length, 1 );
		assert.strictEqual( actualStatements[ 0 ].getClaim().getGuid(), 'pie' );

		statementsChanger.remove( statement2 );
		deferred.resolve( { pageinfo: { lastrevid: 13 } } );

		assert.true( statementsChangerStatements.isEmpty() );
		assert.strictEqual(
			statementsChangerStatements.getItemByKey( 'P1' ),
			null
		);
	} );

	QUnit.test( 'save performs correct API call', function ( assert ) {
		var api = {
			setClaim: sinon.spy( function () {
				return $.Deferred().promise();
			} )
		};
		var statementsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function () { return 0; } },
			statementsChangerState,
			new serialization.StatementSerializer()
		);

		statementsChanger.save( newNoValueSnakStatement() );

		assert.true( api.setClaim.calledOnce );
	} );

	QUnit.test( 'save correctly handles API response', function ( assert ) {
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
			statementsChangerState,
			new serialization.StatementSerializer(),
			new serialization.StatementDeserializer()
		);

		return statementsChanger.save( newNoValueSnakStatement() )
		.done( function ( savedStatement ) {
			assert.true(
				savedStatement instanceof datamodel.Statement,
				'save did not resolve with a Statement'
			);
		} );
	} );

	QUnit.test( 'save correctly handles API failures', function ( assert ) {
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
			statementsChangerState,
			new serialization.StatementSerializer(),
			new serialization.StatementDeserializer()
		);

		var done = assert.async();

		statementsChanger.save( newNoValueSnakStatement() )
		.done( function ( savedStatement ) {
			assert.true( false, 'save should have failed' );
		} )
		.fail( function ( error ) {
			assert.true(
				error instanceof wb.api.RepoApiError,
				'save failed with a RepoApiError'
			);

			assert.strictEqual( error.code, 'errorCode' );
		} )
		.always( done );
	} );

	QUnit.test( 'save fires correct hook', function ( assert ) {
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
			statementsChangerState,
			new serialization.StatementSerializer(),
			new serialization.StatementDeserializer(),
			fireHook
		);
		var guid = 'Q1$a69d8233-b677-43e6-a7c6-519f525eab0c';

		var statement = newNoValueSnakStatement( guid );
		statementsChanger.save( statement );

		assert.true( fireHook.notCalled, 'hook should only fire when API call returns' );

		deferred.resolve( {
			claim: {
				mainsnak: { snaktype: 'novalue', property: 'P1' },
				id: guid,
				rank: 'normal'
			},
			pageinfo: {}
		} );

		assert.true( fireHook.calledOnce, 'hook should have fired' );
		assert.true(
			fireHook.calledWithExactly(
				'wikibase.statement.saved',
				'Q1',
				guid,
				null,
				statement
			),
			'hook should have correct arguments'
		);
	} );

	QUnit.test( 'save properly updates StatementsChangerState', function ( assert ) {
		var deferred;
		var api = {
			setClaim: function () {
				deferred = $.Deferred();
				return deferred.promise();
			}
		};
		var statement1 = newNoValueSnakStatement( 'apple' );
		var statement2 = newNoValueSnakStatement( 'pie' );
		var statementsChangerState1 = new wb.entityChangers.StatementsChangerState(
			'Q1',
			new datamodel.StatementGroupSet()
		);
		var statementsChangerStatements = statementsChangerState1.getStatements();
		var statementsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function () { return 0; }, setClaimRevision: function () {} },
			statementsChangerState1,
			new serialization.StatementSerializer(),
			new serialization.StatementDeserializer()
		);

		assert.strictEqual( statementsChangerStatements.getItemByKey( 'P1' ), null );

		statementsChanger.save( statement1 );
		deferred.resolve( {
			claim: {
				mainsnak: { snaktype: 'novalue', property: 'P1' },
				id: statement1.getClaim().getGuid(),
				rank: 'normal'
			},
			pageinfo: {}
		} );

		assert.strictEqual(
			statementsChangerStatements.getItemByKey( 'P1' ).getItemContainer().length,
			1
		);

		statementsChanger.save( statement2 );
		deferred.resolve( {
			claim: {
				mainsnak: { snaktype: 'novalue', property: 'P1' },
				id: statement2.getClaim().getGuid(),
				rank: 'normal'
			},
			pageinfo: {}
		} );

		assert.strictEqual(
			statementsChangerStatements.getItemByKey( 'P1' ).getItemContainer().length,
			2
		);

		// Change statement1 to contain a somevalue snak
		statement1.setClaim(
			new datamodel.Claim(
				new datamodel.PropertySomeValueSnak( 'P1' ),
				null,
				statement1.getClaim().getGuid()
			)
		);
		statementsChanger.save( statement1 );
		deferred.resolve( {
			claim: {
				mainsnak: { snaktype: 'somevalue', property: 'P1' },
				id: statement1.getClaim().getGuid(),
				rank: 'normal'
			},
			pageinfo: {}
		} );
		assert.strictEqual(
			statementsChangerStatements.getItemByKey( 'P1' ).getItemContainer().length,
			2
		);

		var actualStatements = statementsChangerStatements.getItemByKey( 'P1' ).getItemContainer().toArray();
		assert.strictEqual(
			actualStatements[ 0 ].getClaim().getMainSnak().getType(),
			'novalue'
		);
		assert.strictEqual(
			actualStatements[ 1 ].getClaim().getMainSnak().getType(),
			'somevalue'
		);
	} );

}( wikibase ) );
