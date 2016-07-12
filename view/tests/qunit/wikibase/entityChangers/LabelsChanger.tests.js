/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( sinon, wb, $ ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.LabelsChanger', QUnit.newMwEnvironment() );

	var SUBJECT = wikibase.entityChangers.LabelsChanger;

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

	QUnit.test( 'setLabel performs correct API call', function( assert ) {
		assert.expect( 1 );
		var api = {
			setLabel: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var labelsChanger = new SUBJECT(
			api,
			{ getLabelRevision: function() { return 0; } },
			new wb.datamodel.Item( 'Q1' )
		);

		labelsChanger.setLabel( new wb.datamodel.Term( 'language', 'label' ) );

		assert.ok( api.setLabel.calledOnce );
	} );

	QUnit.test( 'setLabel correctly handles API response', function( assert ) {
		assert.expect( 1 );
		var api = {
			setLabel: sinon.spy( function() {
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
			{ getLabelRevision: function() { return 0; }, setLabelRevision: function() {} },
			new wb.datamodel.Item( 'Q1' )
		);

		QUnit.stop();

		labelsChanger.setLabel( new wb.datamodel.Term( 'language', 'label' ) )
		.done( function( savedLabel ) {
			QUnit.start();
			assert.equal( savedLabel.getText(), 'label' );
		} )
		.fail( function() {
			assert.ok( false, 'setLabel failed' );
		} );
	} );

	QUnit.test( 'setLabel correctly handles API failures', function( assert ) {
		assert.expect( 2 );
		var api = {
			setLabel: sinon.spy( function() {
				return $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise();
			} )
		};
		var labelsChanger = new SUBJECT(
			api,
			{ getLabelRevision: function() { return 0; }, setLabelRevision: function() {} },
			new wb.datamodel.Item( 'Q1' )
		);

		QUnit.stop();

		labelsChanger.setLabel( new wb.datamodel.Term( 'language', 'label' ) )
		.done( function( savedLabel ) {
			assert.ok( false, 'setLabel should have failed' );
		} )
		.fail( function( error ) {
			QUnit.start();
			assert.ok( error instanceof wb.api.RepoApiError, 'setLabel did not fail with a RepoApiError' );
			assert.equal( error.code, 'errorCode' );
		} );
	} );

} )( sinon, wikibase, jQuery );
