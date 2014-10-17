/**
 * @licence GNU GPL v2+
 * @author Adrian Lang < adrian.lang@wikimedia.de >
 */
( function( sinon, wb, $ ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.DescriptionsChanger', QUnit.newMwEnvironment() );

	var SUBJECT = wikibase.entityChangers.DescriptionsChanger;

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

	QUnit.test( 'setDescription performs correct API call', function( assert ) {
		var api = {
			setDescription: sinon.spy( function() {
				return $.Deferred().promise();
			} ),
		};
		var descriptionsChanger = new SUBJECT(
			api,
			{ getDescriptionRevision: function() { return 0; } },
			new wb.datamodel.Item( 'Q1' )
		);

		descriptionsChanger.setDescription( 'description', 'language' );

		assert.ok( api.setDescription.calledOnce );
	} );

	QUnit.test( 'setDescription correctly handles API response', function( assert ) {
		var api = {
			setDescription: sinon.spy( function() {
				return $.Deferred().resolve( {
					entity: {
						descriptions: {
							language: {
								value: 'description'
							},
							lastrevid: 'lastrevid'
						}
					}
				} ).promise();
			} ),
		};
		var descriptionsChanger = new SUBJECT(
			api,
			{ getDescriptionRevision: function() { return 0; }, setDescriptionRevision: function() {} },
			new wb.datamodel.Item( 'Q1' )
		);

		QUnit.stop();

		descriptionsChanger.setDescription( 'description', 'language' )
		.done( function( savedDescription ) {
			QUnit.start();
			assert.equal( savedDescription, 'description' );
		} )
		.fail( function() {
			assert.ok( false, 'setDescription failed' );
		} );
	} );

	QUnit.test( 'setDescription correctly handles API failures', function( assert ) {
		var api = {
			setDescription: sinon.spy( function() {
				return $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise();
			} ),
		};
		var descriptionsChanger = new SUBJECT(
			api,
			{ getDescriptionRevision: function() { return 0; }, setDescriptionRevision: function() {} },
			new wb.datamodel.Item( 'Q1' )
		);

		QUnit.stop();

		descriptionsChanger.setDescription( 'description', 'language' )
		.done( function( savedDescription ) {
			assert.ok( false, 'setDescription should have failed' );
		} )
		.fail( function( error ) {
			QUnit.start();
			assert.ok( error instanceof wb.RepoApiError, 'setDescription did not fail with a RepoApiError' );
			assert.equal( error.code, 'errorCode' );
		} );
	} );

} )( sinon, wikibase, jQuery );
