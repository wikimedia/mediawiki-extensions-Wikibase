/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.LabelsChanger', QUnit.newMwEnvironment() );

	var SUBJECT = wikibase.entityChangers.LabelsChanger,
		datamodel = require( 'wikibase.datamodel' );

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

	QUnit.test( 'setLabel performs correct API call', function ( assert ) {
		var api = {
			setLabel: sinon.spy( function () {
				return $.Deferred().promise();
			} )
		};
		var labelsChanger = new SUBJECT(
			api,
			{ getLabelRevision: function () { return 0; } },
			new datamodel.Item( 'Q1' )
		);

		labelsChanger.setLabel( new datamodel.Term( 'language', 'label' ) );

		assert.true( api.setLabel.calledOnce );
	} );

	QUnit.test( 'setLabel correctly handles API response', function ( assert ) {
		var api = {
			setLabel: sinon.spy( function () {
				return $.Deferred().resolve( {
					entity: {
						labels: {
							language: {
								value: 'label'
							},
							lastrevid: 'lastrevid'
						}
					}
				} ).promise();
			} )
		};
		var labelsChanger = new SUBJECT(
			api,
			{ getLabelRevision: function () { return 0; }, setLabelRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		return labelsChanger.setLabel( new datamodel.Term( 'language', 'label' ) )
		.done( function ( savedLabel ) {
			assert.strictEqual( savedLabel.getText(), 'label' );
		} );
	} );

	QUnit.test( 'setLabel correctly handles API failures', function ( assert ) {
		var api = {
			setLabel: sinon.spy( function () {
				return $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise();
			} )
		};
		var labelsChanger = new SUBJECT(
			api,
			{ getLabelRevision: function () { return 0; }, setLabelRevision: function () {} },
			new datamodel.Item( 'Q1' )
		);

		var done = assert.async();

		labelsChanger.setLabel( new datamodel.Term( 'language', 'label' ) )
		.done( function ( savedLabel ) {
			assert.true( false, 'setLabel should have failed' );
		} )
		.fail( function ( error ) {
			assert.true( error instanceof wb.api.RepoApiError, 'setLabel did not fail with a RepoApiError' );
			assert.strictEqual( error.code, 'errorCode' );
		} )
		.always( done );
	} );

}( wikibase ) );
