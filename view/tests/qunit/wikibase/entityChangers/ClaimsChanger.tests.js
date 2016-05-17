/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( sinon, wb, $ ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.ClaimsChanger' );

	var SUBJECT = wikibase.entityChangers.ClaimsChanger;

	QUnit.test( 'is a function', function( assert ) {
		assert.expect( 1 );
		assert.equal(
			typeof SUBJECT,
			'function',
			'is a function.'
		);
	} );

	QUnit.test( 'is a constructor', function( assert ) {
		assert.expect( 1 );
		assert.ok( new SUBJECT() instanceof SUBJECT );
	} );

	QUnit.test( 'removeStatement performs correct API call', function( assert ) {
		assert.expect( 1 );
		var api = {
			removeClaim: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var claimsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function() { return 0; } },
			'entity'
		);

		claimsChanger.removeStatement(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		);

		assert.ok( api.removeClaim.calledOnce );
	} );

	QUnit.test( 'removeStatement correctly handles API response', function( assert ) {
		assert.expect( 1 );
		var api = {
			removeClaim: sinon.spy( function() {
				return $.Deferred().resolve( {
					claims: [],
					pageinfo: {}
				} ).promise();
			} )
		};
		var claimsChanger = new SUBJECT(
			api,
			{
				getClaimRevision: function() { return 0; },
				setClaimRevision: function() {}
			},
			'entity'
		);

		QUnit.stop();

		claimsChanger.removeStatement(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		)
		.done( function() {
			assert.ok( true, 'removeStatement succeeded' );
		} )
		.fail( function() {
			assert.ok( false, 'removeStatement failed' );
		} )
		.always( function() {
			QUnit.start();
		} );
	} );

	QUnit.test( 'removeStatement correctly handles API failures', function( assert ) {
		assert.expect( 2 );
		var api = {
			removeClaim: sinon.spy( function() {
				return $.Deferred()
					.reject( 'errorCode', { error: { code: 'errorCode' } } )
					.promise();
			} )
		};
		var claimsChanger = new SUBJECT(
			api,
			{
				getClaimRevision: function() { return 0; },
				setClaimRevision: function() {}
			},
			'entity'
		);

		QUnit.stop();

		claimsChanger.removeStatement(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		)
		.done( function() {
			assert.ok( false, 'removeStatement should have failed' );
		} )
		.fail( function( error ) {
			assert.ok(
				error instanceof wb.api.RepoApiError,
				'removeStatement did not fail with a RepoApiError'
			);

			assert.equal( error.code, 'errorCode' );
		} )
		.always( function() {
			QUnit.start();
		} );
	} );

	QUnit.test( 'setClaim performs correct API call', function( assert ) {
		assert.expect( 1 );
		var api = {
			setClaim: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var claimsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function() { return 0; } },
			'entity',
			new wb.serialization.ClaimSerializer()
		);

		claimsChanger.setClaim(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		);

		assert.ok( api.setClaim.calledOnce );
	} );

	QUnit.test( 'setClaim correctly handles API response', function( assert ) {
		assert.expect( 1 );
		var api = {
			setClaim: sinon.spy( function() {
				return $.Deferred().resolve( {
					claim: { mainsnak: { snaktype: 'novalue', property: 'P1' } },
					pageinfo: {}
				} ).promise();
			} )
		};
		var claimsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function() { return 0; }, setClaimRevision: function() {} },
			'entity',
			new wb.serialization.ClaimSerializer(),
			new wb.serialization.ClaimDeserializer()
		);

		QUnit.stop();

		claimsChanger.setClaim(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		)
		.done( function( savedClaim ) {
			assert.ok(
				savedClaim instanceof wb.datamodel.Claim,
				'setClaim did not resolve with a Claim'
			);
		} )
		.fail( function() {
			assert.ok( false, 'setClaim failed' );
		} )
		.always( function() {
			QUnit.start();
		} );
	} );

	QUnit.test( 'setClaim correctly handles API failures', function( assert ) {
		assert.expect( 2 );
		var api = {
			setClaim: sinon.spy( function() {
				return $.Deferred()
					.reject( 'errorCode', { error: { code: 'errorCode' } } )
					.promise();
			} )
		};
		var claimsChanger = new SUBJECT(
			api,
			{
				getClaimRevision: function() { return 0; },
				setClaimRevision: function() {}
			},
			'entity',
			new wb.serialization.ClaimSerializer(),
			new wb.serialization.ClaimDeserializer()
		);

		QUnit.stop();

		claimsChanger.setClaim(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) )
		)
		.done( function( savedClaim ) {
			assert.ok( false, 'setClaim should have failed' );
		} )
		.fail( function( error ) {
			assert.ok(
				error instanceof wb.api.RepoApiError,
				'setClaim failed with a RepoApiError'
			);

			assert.equal( error.code, 'errorCode' );
		} )
		.always( function() {
			QUnit.start();
		} );
	} );

	QUnit.test( 'setStatement performs correct API call', function( assert ) {
		assert.expect( 1 );
		var api = {
			setClaim: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var claimsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function() { return 0; } },
			'entity',
			null,
			null,
			new wb.serialization.StatementSerializer()
		);

		claimsChanger.setStatement(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		);

		assert.ok( api.setClaim.calledOnce );
	} );

	QUnit.test( 'setStatement correctly handles API response', function( assert ) {
		assert.expect( 1 );
		var api = {
			setClaim: sinon.spy( function() {
				return $.Deferred().resolve( {
					claim: {
						mainsnak: { snaktype: 'novalue', property: 'P1' },
						rank: 'normal'
					},
					pageinfo: {}
				} ).promise();
			} )
		};
		var claimsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function() { return 0; }, setClaimRevision: function() {} },
			'entity',
			null,
			null,
			new wb.serialization.StatementSerializer(),
			new wb.serialization.StatementDeserializer()
		);

		QUnit.stop();

		claimsChanger.setStatement(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		)
		.done( function( savedStatement ) {
			assert.ok(
				savedStatement instanceof wb.datamodel.Statement,
				'setStatement did not resolve with a Statement'
			);
		} )
		.fail( function() {
			assert.ok( false, 'setStatement failed' );
		} )
		.always( function() {
			QUnit.start();
		} );
	} );

	QUnit.test( 'setStatement correctly handles API failures', function( assert ) {
		assert.expect( 2 );
		var api = {
			setClaim: sinon.spy( function() {
				return $.Deferred()
					.reject( 'errorCode', { error: { code: 'errorCode' } } )
					.promise();
			} )
		};
		var claimsChanger = new SUBJECT(
			api,
			{
				getClaimRevision: function() { return 0; },
				setClaimRevision: function() {}
			},
			'entity',
			null,
			null,
			new wb.serialization.StatementSerializer(),
			new wb.serialization.StatementDeserializer()
		);

		QUnit.stop();

		claimsChanger.setStatement(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		)
		.done( function( savedStatement ) {
			assert.ok( false, 'setStatement should have failed' );
		} )
		.fail( function( error ) {
			assert.ok(
				error instanceof wb.api.RepoApiError,
				'setStatement failed with a RepoApiError'
			);

			assert.equal( error.code, 'errorCode' );
		} )
		.always( function() {
			QUnit.start();
		} );
	} );

} )( sinon, wikibase, jQuery );
