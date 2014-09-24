/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( sinon, wb, $ ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.ClaimsChanger', QUnit.newMwEnvironment() );

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

	QUnit.test( 'removeClaim performs correct API call', function( assert ) {
		var api = {
			removeClaim: sinon.spy( function() {
				return $.Deferred().promise();
			} ),
		};
		var claimsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function() { return 0; } },
			'entity'
		);

		claimsChanger.removeClaim(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ),
			'index'
		);

		assert.ok( api.removeClaim.calledOnce );
	} );

	QUnit.test( 'removeClaim correctly handles API response', function( assert ) {
		var api = {
			removeClaim: sinon.spy( function() {
				return $.Deferred().resolve( {
					claims: [],
					pageinfo: {}
				} ).promise();
			} ),
		};
		var claimsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function() { return 0; }, setClaimRevision: function() {} },
			'entity'
		);

		QUnit.stop();

		claimsChanger.removeClaim(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ),
			'index'
		)
		.done( function() {
			QUnit.start();
			assert.ok( true, 'removeClaim succeeded' );
		} )
		.fail( function() {
			assert.ok( false, 'removeClaim failed' );
		} );
	} );

	QUnit.test( 'removeClaim correctly handles API failures', function( assert ) {
		var api = {
			removeClaim: sinon.spy( function() {
				return $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise();
			} ),
		};
		var claimsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function() { return 0; }, setClaimRevision: function() {} },
			'entity'
		);

		QUnit.stop();

		claimsChanger.removeClaim(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ),
			'index'
		)
		.done( function() {
			assert.ok( false, 'removeClaim should have failed' );
		} )
		.fail( function( error ) {
			QUnit.start();
			assert.ok( error instanceof wb.RepoApiError, 'removeClaim did not fail with a RepoApiError' );
			assert.equal( error.code, 'errorCode' );
		} );
	} );

	QUnit.test( 'setClaim performs correct API call', function( assert ) {
		var api = {
			setClaim: sinon.spy( function() {
				return $.Deferred().promise();
			} ),
		};
		var claimsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function() { return 0; } },
			'entity'
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
			} ),
		};
		var claimsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function() { return 0; }, setClaimRevision: function() {} },
			'entity'
		);

		QUnit.stop();

		claimsChanger.setClaim(
			new wb.datamodel.Claim( new wb.datamodel.PropertyNoValueSnak( 'P1' ) ),
			'index'
		)
		.done( function( savedClaim ) {
			QUnit.start();
			assert.ok( savedClaim instanceof wb.datamodel.Claim, 'setClaim did not resolve with a Claim' );
		} )
		.fail( function() {
			assert.ok( false, 'setClaim failed' );
		} );
	} );

	QUnit.test( 'setClaim correctly handles API failures', function( assert ) {
		var api = {
			setClaim: sinon.spy( function() {
				return $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise();
			} ),
		};
		var claimsChanger = new SUBJECT(
			api,
			{ getClaimRevision: function() { return 0; }, setClaimRevision: function() {} },
			'entity'
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
			assert.ok( error instanceof wb.RepoApiError, 'setClaim did not fail with a RepoApiError' );
			assert.equal( error.code, 'errorCode' );
		} );
	} );

} )( sinon, wikibase, jQuery );
