/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( sinon, wb, $ ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.ClaimsChanger' );

	var SUBJECT = wikibase.entityChangers.ClaimsChanger;

	QUnit.test( 'is a function', function( assert ) {
		assert.equal(
			typeof SUBJECT,
			'function',
			'is a function.'
		);
	} );

	QUnit.test( 'is a constructor', function( assert ) {
		assert.ok( new SUBJECT() instanceof SUBJECT );
	} );

	QUnit.test( 'removeStatement performs correct API call', function( assert ) {
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
			new wb.datamodel.Statement( new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ) ),
			'index'
		);

		assert.ok( api.removeClaim.calledOnce );
	} );

	QUnit.test( 'removeStatement correctly handles API response', function( assert ) {
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
			new wb.datamodel.Statement( new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ) ),
			'index'
		)
		.done( function() {
			QUnit.start();
			assert.ok( true, 'removeStatement succeeded' );
		} )
		.fail( function() {
			assert.ok( false, 'removeStatement failed' );
		} );
	} );

	QUnit.test( 'removeStatement correctly handles API failures', function( assert ) {
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
			new wb.datamodel.Statement( new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ) ),
			'index'
		)
		.done( function() {
			assert.ok( false, 'removeStatement should have failed' );
		} )
		.fail( function( error ) {
			QUnit.start();

			assert.ok(
				error instanceof wb.RepoApiError,
				'removeStatement did not fail with a RepoApiError'
			);

			assert.equal( error.code, 'errorCode' );
		} );
	} );

	QUnit.test( 'setClaim performs correct API call', function( assert ) {
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
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ),
			'index'
		);

		assert.ok( api.setClaim.calledOnce );
	} );

	QUnit.test( 'setClaim correctly handles API response', function( assert ) {
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
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ),
			'index'
		)
		.done( function( savedClaim ) {
			QUnit.start();

			assert.ok(
				savedClaim instanceof wb.datamodel.Claim,
				'setClaim did not resolve with a Claim'
			);
		} )
		.fail( function() {
			assert.ok( false, 'setClaim failed' );
		} );
	} );

	QUnit.test( 'setClaim correctly handles API failures', function( assert ) {
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
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ),
			'index'
		)
		.done( function( savedClaim ) {
			assert.ok( false, 'setClaim should have failed' );
		} )
		.fail( function( error ) {
			QUnit.start();

			assert.ok(
				error instanceof wb.RepoApiError,
				'setClaim failed with a RepoApiError'
			);

			assert.equal( error.code, 'errorCode' );
		} );
	} );

	QUnit.test( 'setStatement performs correct API call', function( assert ) {
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
			new wb.datamodel.Statement( new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ) ),
			'index'
		);

		assert.ok( api.setClaim.calledOnce );
	} );

	QUnit.test( 'setStatement correctly handles API response', function( assert ) {
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
			new wb.datamodel.Statement( new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ) ),
			'index'
		)
		.done( function( savedStatement ) {
			QUnit.start();

			assert.ok(
				savedStatement instanceof wb.datamodel.Statement,
				'setStatement did not resolve with a Statement'
			);
		} )
		.fail( function() {
			assert.ok( false, 'setStatement failed' );
		} );
	} );

	QUnit.test( 'setStatement correctly handles API failures', function( assert ) {
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
			new wb.datamodel.Statement( new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ) ),
			'index'
		)
		.done( function( savedStatement ) {
			assert.ok( false, 'setStatement should have failed' );
		} )
		.fail( function( error ) {
			QUnit.start();

			assert.ok(
				error instanceof wb.RepoApiError,
				'setStatement failed with a RepoApiError'
			);

			assert.equal( error.code, 'errorCode' );
		} );
	} );

} )( sinon, wikibase, jQuery );
