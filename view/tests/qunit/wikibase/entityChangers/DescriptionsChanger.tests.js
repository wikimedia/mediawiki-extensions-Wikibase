/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.DescriptionsChanger', QUnit.newMwEnvironment() );

	var SUBJECT = wikibase.entityChangers.DescriptionsChanger,
		TempUserWatcher = wikibase.entityChangers.TempUserWatcher,
		datamodel = require( 'wikibase.datamodel' );

	QUnit.test( 'is a function', ( assert ) => {
		assert.strictEqual(
			typeof SUBJECT,
			'function',
			'is a function.'
		);
	} );

	QUnit.test( 'is a constructor', ( assert ) => {
		assert.true( new SUBJECT() instanceof SUBJECT );
	} );

	QUnit.test( 'setDescription performs correct API call', ( assert ) => {
		var api = {
			setDescription: sinon.spy( () => $.Deferred().promise() )
		};
		var descriptionsChanger = new SUBJECT(
			api,
			{ getDescriptionRevision: function () {
				return 0;
			} },
			new datamodel.Item( 'Q1' )
		);

		descriptionsChanger.setDescription( new datamodel.Term( 'language', 'description' ), new TempUserWatcher() );

		assert.true( api.setDescription.calledOnce );
	} );

	QUnit.test( 'setDescription correctly handles API response', ( assert ) => {
		var api = {
			setDescription: sinon.spy( () => $.Deferred().resolve( {
				entity: {
					descriptions: {
						language: {
							value: 'description'
						},
						lastrevid: 'lastrevid'
					}
				}
			} ).promise() )
		};
		var descriptionsChanger = new SUBJECT(
			api,
			{ getDescriptionRevision: function () {
				return 0;
			}, setDescriptionRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		return descriptionsChanger.setDescription( new datamodel.Term( 'language', 'description' ), new TempUserWatcher() )
		.done( ( savedDescription ) => {
			assert.strictEqual( savedDescription.getText(), 'description' );
		} );
	} );

	QUnit.test( 'setDescription correctly handles API failures', ( assert ) => {
		var api = {
			setDescription: sinon.spy( () => $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise() )
		};
		var descriptionsChanger = new SUBJECT(
			api,
			{ getDescriptionRevision: function () {
				return 0;
			}, setDescriptionRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		var done = assert.async();

		descriptionsChanger.setDescription( new datamodel.Term( 'language', 'description' ), new TempUserWatcher() )
		.done( ( savedDescription ) => {
			assert.true( false, 'setDescription should have failed' );
		} )
		.fail( ( error ) => {
			assert.true( error instanceof wb.api.RepoApiError, 'setDescription did not fail with a RepoApiError' );
			assert.strictEqual( error.code, 'errorCode' );
		} )
		.always( done );
	} );

	QUnit.test( 'sets redirect Url if present', ( assert ) => {
		const target = 'https://wiki.example/';
		const tempUserWatcher = new TempUserWatcher();

		var api = {
			setDescription: sinon.spy( () => $.Deferred().resolve( {
				entity: {
					descriptions: {
						language: {
							value: 'description'
						},
						lastrevid: 'lastrevid'
					}
				},
				tempusercreated: 'SomeUser',
				tempuserredirect: target
			} ).promise() )
		};
		var descriptionsChanger = new SUBJECT(
			api,
			{ getDescriptionRevision: function () {
				return 0;
			}, setDescriptionRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		return descriptionsChanger.setDescription( new datamodel.Term( 'language', 'description' ), tempUserWatcher )
			.done( ( _savedDescription ) => {
				assert.true( true, 'setDescription succeeded' );
				assert.strictEqual( target, tempUserWatcher.getRedirectUrl(), 'it should set the URL' );
			} );
	} );

}( wikibase ) );
