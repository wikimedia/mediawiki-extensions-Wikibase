/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( sinon, wb, $ ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.ReferencesChanger' );

	var SUBJECT = wikibase.entityChangers.ReferencesChanger;

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

	QUnit.test( 'removeReference performs correct API call', function( assert ) {
		var api = {
			removeReferences: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var referencesChanger = new SUBJECT(
			api,
			{ getClaimRevision: function() { return 0; } },
			'entity'
		);

		referencesChanger.removeReference(
			'',
			new wb.datamodel.Reference(),
			'index'
		);

		assert.ok( api.removeReferences.calledOnce );
	} );

	QUnit.test( 'removeReference correctly handles API response', function( assert ) {
		var api = {
			removeReferences: sinon.spy( function() {
				return $.Deferred().resolve( {
					references: [],
					pageinfo: {}
				} ).promise();
			} )
		};
		var referencesChanger = new SUBJECT(
			api,
			{
				getClaimRevision: function() { return 0; },
				setClaimRevision: function() {}
			},
			'entity'
		);

		QUnit.stop();

		referencesChanger.removeReference(
			'',
			new wb.datamodel.Reference(),
			'index'
		)
		.done( function() {
			QUnit.start();
			assert.ok( true, 'removeReference succeeded' );
		} )
		.fail( function() {
			assert.ok( false, 'removeReference failed' );
		} );
	} );

	QUnit.test( 'removeReference correctly handles API failures', function( assert ) {
		var api = {
			removeReferences: sinon.spy( function() {
				return $.Deferred()
					.reject( 'errorCode', { error: { code: 'errorCode' } } )
					.promise();
			} )
		};
		var referencesChanger = new SUBJECT(
			api,
			{
				getClaimRevision: function() { return 0; },
				setClaimRevision: function() {}
			},
			'entity'
		);

		QUnit.stop();

		referencesChanger.removeReference(
			'',
			new wb.datamodel.Reference(),
			'index'
		).done( function() {
			assert.ok( false, 'removeReference should have failed' );
		} )
		.fail( function( error ) {
			QUnit.start();

			assert.ok(
				error instanceof wb.RepoApiError,
				'removeReference did not fail with a RepoApiError'
			);

			assert.equal( error.code, 'errorCode' );
		} );
	} );

	QUnit.test( 'setReference performs correct API call', function( assert ) {
		var api = {
			setReference: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var referencesChanger = new SUBJECT(
			api,
			{ getClaimRevision: function() { return 0; } },
			'entity',
			new wb.serialization.ReferenceSerializer()
		);

		referencesChanger.setReference(
			'',
			new wb.datamodel.Reference(),
			'index'
		);

		assert.ok( api.setReference.calledOnce );
	} );

	QUnit.test( 'setReference correctly handles API response', function( assert ) {
		var api = {
			setReference: sinon.spy( function() {
				return $.Deferred().resolve( {
					reference: { snaks: [] },
					pageinfo: {}
				} ).promise();
			} )
		};
		var referencesChanger = new SUBJECT(
			api,
			{ getClaimRevision: function() { return 0; }, setClaimRevision: function() {} },
			'entity',
			new wb.serialization.ReferenceSerializer(),
			new wb.serialization.ReferenceDeserializer()
		);

		QUnit.stop();

		referencesChanger.setReference(
			'',
			new wb.datamodel.Reference(),
			'index'
		)
		.done( function( savedReference ) {
			QUnit.start();
			assert.ok( savedReference instanceof wb.datamodel.Reference, 'setReference did not resolve with a Reference' );
		} )
		.fail( function() {
			assert.ok( false, 'setReference failed' );
		} );
	} );

	QUnit.test( 'setReference correctly handles API failures', function( assert ) {
		var api = {
			setReference: sinon.spy( function() {
				return $.Deferred()
					.reject( 'errorCode', { error: { code: 'errorCode' } } )
					.promise();
			} )
		};
		var referencesChanger = new SUBJECT(
			api,
			{
				getClaimRevision: function() { return 0; },
				setClaimRevision: function() {}
			},
			'entity',
			new wb.serialization.ReferenceSerializer()
		);

		QUnit.stop();

		referencesChanger.setReference(
			'',
			new wb.datamodel.Reference(),
			'index'
		)
		.done( function() {
			assert.ok( false, 'setReference should have failed' );
		} )
		.fail( function( error ) {
			QUnit.start();

			assert.ok(
				error instanceof wb.RepoApiError,
				'setReference did not fail with a RepoApiError'
			);

			assert.equal( error.code, 'errorCode' );
		} );
	} );

} )( sinon, wikibase, jQuery );
