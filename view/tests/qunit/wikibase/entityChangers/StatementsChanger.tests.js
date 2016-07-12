/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( sinon, wb, $ ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.StatementsChanger' );

	var SUBJECT = wikibase.entityChangers.StatementsChanger;

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

	QUnit.test( 'remove performs correct API call', function( assert ) {
		assert.expect( 1 );
		var api = {
			removeClaim: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var statementsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function() { return 0; } },
			'entity'
		);

		statementsChanger.remove(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		);

		assert.ok( api.removeClaim.calledOnce );
	} );

	QUnit.test( 'remove correctly handles API response', function( assert ) {
		assert.expect( 1 );
		var api = {
			removeClaim: sinon.spy( function() {
				return $.Deferred().resolve( {
					claims: [],
					pageinfo: {}
				} ).promise();
			} )
		};
		var statementsChanger = new SUBJECT(
			api,
			{
				getClaimRevision: function() { return 0; },
				setClaimRevision: function() {}
			},
			'entity'
		);

		QUnit.stop();

		statementsChanger.remove(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		)
		.done( function() {
			assert.ok( true, 'remove succeeded' );
		} )
		.fail( function() {
			assert.ok( false, 'remove failed' );
		} )
		.always( function() {
			QUnit.start();
		} );
	} );

	QUnit.test( 'remove correctly handles API failures', function( assert ) {
		assert.expect( 2 );
		var api = {
			removeClaim: sinon.spy( function() {
				return $.Deferred()
					.reject( 'errorCode', { error: { code: 'errorCode' } } )
					.promise();
			} )
		};
		var statementsChanger = new SUBJECT(
			api,
			{
				getClaimRevision: function() { return 0; },
				setClaimRevision: function() {}
			},
			'entity'
		);

		QUnit.stop();

		statementsChanger.remove(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		)
		.done( function() {
			assert.ok( false, 'remove should have failed' );
		} )
		.fail( function( error ) {
			assert.ok(
				error instanceof wb.api.RepoApiError,
				'remove did not fail with a RepoApiError'
			);

			assert.equal( error.code, 'errorCode' );
		} )
		.always( function() {
			QUnit.start();
		} );
	} );

	QUnit.test( 'save performs correct API call', function( assert ) {
		assert.expect( 1 );
		var api = {
			setClaim: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var statementsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function() { return 0; } },
			'entity',
			new wb.serialization.StatementSerializer()
		);

		statementsChanger.save(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		);

		assert.ok( api.setClaim.calledOnce );
	} );

	QUnit.test( 'save correctly handles API response', function( assert ) {
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
		var statementsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function() { return 0; }, setClaimRevision: function() {} },
			'entity',
			new wb.serialization.StatementSerializer(),
			new wb.serialization.StatementDeserializer()
		);

		QUnit.stop();

		statementsChanger.save(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		)
		.done( function( savedStatement ) {
			assert.ok(
				savedStatement instanceof wb.datamodel.Statement,
				'save did not resolve with a Statement'
			);
		} )
		.fail( function() {
			assert.ok( false, 'save failed' );
		} )
		.always( function() {
			QUnit.start();
		} );
	} );

	QUnit.test( 'save correctly handles API failures', function( assert ) {
		assert.expect( 2 );
		var api = {
			setClaim: sinon.spy( function() {
				return $.Deferred()
					.reject( 'errorCode', { error: { code: 'errorCode' } } )
					.promise();
			} )
		};
		var statementsChanger = new SUBJECT(
			api,
			{
				getClaimRevision: function() { return 0; },
				setClaimRevision: function() {}
			},
			'entity',
			new wb.serialization.StatementSerializer(),
			new wb.serialization.StatementDeserializer()
		);

		QUnit.stop();

		statementsChanger.save(
			new wb.datamodel.Statement( new wb.datamodel.Claim(
				new wb.datamodel.PropertyNoValueSnak( 'P1' )
			) )
		)
		.done( function( savedStatement ) {
			assert.ok( false, 'save should have failed' );
		} )
		.fail( function( error ) {
			assert.ok(
				error instanceof wb.api.RepoApiError,
				'save failed with a RepoApiError'
			);

			assert.equal( error.code, 'errorCode' );
		} )
		.always( function() {
			QUnit.start();
		} );
	} );

} )( sinon, wikibase, jQuery );
