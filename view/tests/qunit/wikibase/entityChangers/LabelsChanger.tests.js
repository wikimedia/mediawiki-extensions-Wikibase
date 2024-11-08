/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.LabelsChanger', QUnit.newMwEnvironment() );

	var SUBJECT = wikibase.entityChangers.LabelsChanger,
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

	QUnit.test( 'setLabel performs correct API call', ( assert ) => {
		var api = {
			setLabel: sinon.spy( () => $.Deferred().promise() )
		};
		var labelsChanger = new SUBJECT(
			api,
			{ getLabelRevision: function () {
				return 0;
			} },
			new datamodel.Item( 'Q1' )
		);

		labelsChanger.setLabel( new datamodel.Term( 'language', 'label' ), new TempUserWatcher() );

		assert.true( api.setLabel.calledOnce );
	} );

	QUnit.test( 'setLabel correctly handles API response', ( assert ) => {
		var api = {
			setLabel: sinon.spy( () => $.Deferred().resolve( {
				entity: {
					labels: {
						language: {
							value: 'label'
						},
						lastrevid: 'lastrevid'
					}
				}
			} ).promise() )
		};
		var labelsChanger = new SUBJECT(
			api,
			{ getLabelRevision: function () {
				return 0;
			}, setLabelRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		return labelsChanger.setLabel( new datamodel.Term( 'language', 'label' ), new TempUserWatcher() )
		.done( ( savedLabel ) => {
			assert.strictEqual( savedLabel.getText(), 'label' );
		} );
	} );

	QUnit.test( 'setLabel correctly handles API failures', ( assert ) => {
		var api = {
			setLabel: sinon.spy( () => $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise() )
		};
		var labelsChanger = new SUBJECT(
			api,
			{ getLabelRevision: function () {
				return 0;
			}, setLabelRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		var done = assert.async();

		labelsChanger.setLabel( new datamodel.Term( 'language', 'label' ), new TempUserWatcher() )
		.done( ( savedLabel ) => {
			assert.true( false, 'setLabel should have failed' );
		} )
		.fail( ( error ) => {
			assert.true( error instanceof wb.api.RepoApiError, 'setLabel did not fail with a RepoApiError' );
			assert.strictEqual( error.code, 'errorCode' );
		} )
		.always( done );
	} );

}( wikibase ) );
