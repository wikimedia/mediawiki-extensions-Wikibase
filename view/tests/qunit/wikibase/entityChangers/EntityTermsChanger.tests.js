/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function( sinon, wb, $ ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.EntityTermsChanger', QUnit.newMwEnvironment() );

	var SUBJECT = wikibase.entityChangers.EntityTermsChanger;

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

	QUnit.test( 'save performs correct API calls for new label', function( assert ) {
		assert.expect( 2 );
		var api = {
			setLabel: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var entityTermsChanger = new SUBJECT(
			api,
			{ getLabelRevision: function() { return 0; } },
			new wb.datamodel.Item( 'Q1' )
		);

		entityTermsChanger.save(
			new wb.datamodel.Fingerprint( new wb.datamodel.TermMap( {
				language: new wb.datamodel.Term( 'language', 'label' )
			} ) ),
			new wb.datamodel.Fingerprint()
		);

		assert.ok( api.setLabel.calledOnce );
		sinon.assert.calledWith( api.setLabel, 'Q1', 0, 'label', 'language' );
	} );

	QUnit.test( 'save performs correct API calls for changed label', function( assert ) {
		assert.expect( 2 );
		var api = {
			setLabel: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var entityTermsChanger = new SUBJECT(
			api,
			{ getLabelRevision: function() { return 0; } },
			new wb.datamodel.Item( 'Q1' )
		);

		entityTermsChanger.save(
			new wb.datamodel.Fingerprint( new wb.datamodel.TermMap( {
				language: new wb.datamodel.Term( 'language', 'new label' )
			} ) ),
			new wb.datamodel.Fingerprint( new wb.datamodel.TermMap( {
				language: new wb.datamodel.Term( 'language', 'old label' )
			} ) )
		);

		assert.ok( api.setLabel.calledOnce );
		sinon.assert.calledWith( api.setLabel, 'Q1', 0, 'new label', 'language' );
	} );

	QUnit.test( 'save performs correct API calls for removed label', function( assert ) {
		assert.expect( 2 );
		var api = {
			setLabel: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var entityTermsChanger = new SUBJECT(
			api,
			{ getLabelRevision: function() { return 0; } },
			new wb.datamodel.Item( 'Q1' )
		);

		entityTermsChanger.save(
			new wb.datamodel.Fingerprint(),
			new wb.datamodel.Fingerprint( new wb.datamodel.TermMap( {
				language: new wb.datamodel.Term( 'language', 'old label' )
			} ) )
		);

		assert.ok( api.setLabel.calledOnce );
		sinon.assert.calledWith( api.setLabel, 'Q1', 0, '', 'language' );
	} );

	QUnit.test( 'save correctly handles API response for labels', function( assert ) {
		assert.expect( 1 );
		var api = {
			setLabel: sinon.spy( function() {
				return $.Deferred().resolve( {
					entity: {
						labels: {
							language: {
								value: 'normalized label'
							},
							lastrevid: 'lastrevid'
						}
					}
				} ).promise();
			} )
		};
		var entityTermsChanger = new SUBJECT(
			api,
			{ getLabelRevision: function() { return 0; }, setLabelRevision: function() {} },
			new wb.datamodel.Item( 'Q1' )
		);

		QUnit.stop();

		entityTermsChanger.save(
			new wb.datamodel.Fingerprint( new wb.datamodel.TermMap( {
				language: new wb.datamodel.Term( 'language', 'label' )
			} ) ),
			new wb.datamodel.Fingerprint()
		).done( function( savedFingerprint ) {
			QUnit.start();
			assert.equal( savedFingerprint.getLabelFor( 'language' ).getText(), 'normalized label' );
		} )
		.fail( function() {
			QUnit.start();
			assert.ok( false, 'save failed' );
		} );
	} );

	QUnit.test( 'save correctly handles API failures for labels', function( assert ) {
		assert.expect( 2 );
		var api = {
			setLabel: sinon.spy( function() {
				return $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise();
			} )
		};
		var entityTermsChanger = new SUBJECT(
			api,
			{ getLabelRevision: function() { return 0; }, setLabelRevision: function() {} },
			new wb.datamodel.Item( 'Q1' )
		);

		QUnit.stop();

		entityTermsChanger.save(
			new wb.datamodel.Fingerprint( new wb.datamodel.TermMap( {
				language: new wb.datamodel.Term( 'language', 'label' )
			} ) ),
			new wb.datamodel.Fingerprint()
		).done( function( savedFingerprint ) {
			QUnit.start();
			assert.ok( false, 'save should have failed' );
		} )
		.fail( function( error ) {
			QUnit.start();
			assert.ok( error instanceof wb.api.RepoApiError, 'save did not fail with a RepoApiError' );
			assert.equal( error.code, 'errorCode' );
		} );
	} );

	QUnit.test( 'save performs correct API calls for new description', function( assert ) {
		assert.expect( 2 );
		var api = {
			setDescription: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var entityTermsChanger = new SUBJECT(
			api,
			{ getDescriptionRevision: function() { return 0; } },
			new wb.datamodel.Item( 'Q1' )
		);

		entityTermsChanger.save(
			new wb.datamodel.Fingerprint(
				null,
				new wb.datamodel.TermMap( {
					language: new wb.datamodel.Term( 'language', 'description' )
				} )
			),
			new wb.datamodel.Fingerprint()
		);

		assert.ok( api.setDescription.calledOnce );
		sinon.assert.calledWith( api.setDescription, 'Q1', 0, 'description', 'language' );
	} );

	QUnit.test( 'save performs correct API calls for changed description', function( assert ) {
		assert.expect( 2 );
		var api = {
			setDescription: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var entityTermsChanger = new SUBJECT(
			api,
			{ getDescriptionRevision: function() { return 0; } },
			new wb.datamodel.Item( 'Q1' )
		);

		entityTermsChanger.save(
			new wb.datamodel.Fingerprint(
				null,
				new wb.datamodel.TermMap( {
					language: new wb.datamodel.Term( 'language', 'new description' )
				} )
			),
			new wb.datamodel.Fingerprint(
				null,
				new wb.datamodel.TermMap( {
					language: new wb.datamodel.Term( 'language', 'old description' )
				} )
			)
		);

		assert.ok( api.setDescription.calledOnce );
		sinon.assert.calledWith( api.setDescription, 'Q1', 0, 'new description', 'language' );
	} );

	QUnit.test( 'save performs correct API calls for removed description', function( assert ) {
		assert.expect( 2 );
		var api = {
			setDescription: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var entityTermsChanger = new SUBJECT(
			api,
			{ getDescriptionRevision: function() { return 0; } },
			new wb.datamodel.Item( 'Q1' )
		);

		entityTermsChanger.save(
			new wb.datamodel.Fingerprint(),
			new wb.datamodel.Fingerprint(
				null,
				new wb.datamodel.TermMap( {
					language: new wb.datamodel.Term( 'language', 'old description' )
				} )
			)
		);

		assert.ok( api.setDescription.calledOnce );
		sinon.assert.calledWith( api.setDescription, 'Q1', 0, '', 'language' );
	} );

	QUnit.test( 'save correctly handles API response for descriptions', function( assert ) {
		assert.expect( 1 );
		var api = {
			setDescription: sinon.spy( function() {
				return $.Deferred().resolve( {
					entity: {
						descriptions: {
							language: {
								value: 'normalized description'
							},
							lastrevid: 'lastrevid'
						}
					}
				} ).promise();
			} )
		};
		var entityTermsChanger = new SUBJECT(
			api,
			{ getDescriptionRevision: function() { return 0; }, setDescriptionRevision: function() {} },
			new wb.datamodel.Item( 'Q1' )
		);

		QUnit.stop();

		entityTermsChanger.save(
			new wb.datamodel.Fingerprint(
				null,
				new wb.datamodel.TermMap( {
					language: new wb.datamodel.Term( 'language', 'description' )
				} )
			),
			new wb.datamodel.Fingerprint()
		).done( function( savedFingerprint ) {
			QUnit.start();
			assert.equal( savedFingerprint.getDescriptionFor( 'language' ).getText(), 'normalized description' );
		} )
		.fail( function() {
			QUnit.start();
			assert.ok( false, 'save failed' );
		} );
	} );

	QUnit.test( 'save correctly handles API failures for descriptions', function( assert ) {
		assert.expect( 2 );
		var api = {
			setDescription: sinon.spy( function() {
				return $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise();
			} )
		};
		var entityTermsChanger = new SUBJECT(
			api,
			{ getDescriptionRevision: function() { return 0; }, setDescriptionRevision: function() {} },
			new wb.datamodel.Item( 'Q1' )
		);

		QUnit.stop();

		entityTermsChanger.save(
			new wb.datamodel.Fingerprint(
				null,
				new wb.datamodel.TermMap( {
					language: new wb.datamodel.Term( 'language', 'description' )
				} )
			),
			new wb.datamodel.Fingerprint()
		).done( function( savedFingerprint ) {
			QUnit.start();
			assert.ok( false, 'save should have failed' );
		} )
		.fail( function( error ) {
			QUnit.start();
			assert.ok( error instanceof wb.api.RepoApiError, 'save did not fail with a RepoApiError' );
			assert.equal( error.code, 'errorCode' );
		} );
	} );

	QUnit.test( 'save performs correct API calls for new aliases', function( assert ) {
		assert.expect( 2 );
		var api = {
			setAliases: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var entityTermsChanger = new SUBJECT(
			api,
			{ getAliasesRevision: function() { return 0; } },
			new wb.datamodel.Item( 'Q1' )
		);

		entityTermsChanger.save(
			new wb.datamodel.Fingerprint(
				null,
				null,
				new wb.datamodel.MultiTermMap( {
					language: new wb.datamodel.MultiTerm( 'language', [ 'alias' ] )
				} )
			),
			new wb.datamodel.Fingerprint()
		);

		assert.ok( api.setAliases.calledOnce );
		sinon.assert.calledWith( api.setAliases, 'Q1', 0, [ 'alias' ], [], 'language' );
	} );

	QUnit.test( 'save performs correct API calls for changed aliases', function( assert ) {
		assert.expect( 2 );
		var api = {
			setAliases: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var entityTermsChanger = new SUBJECT(
			api,
			{ getAliasesRevision: function() { return 0; } },
			new wb.datamodel.Item( 'Q1' )
		);

		entityTermsChanger.save(
			new wb.datamodel.Fingerprint(
				null,
				null,
				new wb.datamodel.MultiTermMap( {
					language: new wb.datamodel.MultiTerm( 'language', [ 'new alias' ] )
				} )
			),
			new wb.datamodel.Fingerprint(
				null,
				null,
				new wb.datamodel.MultiTermMap( {
					language: new wb.datamodel.MultiTerm( 'language', [ 'old alias' ] )
				} )
			)
		);

		assert.ok( api.setAliases.calledOnce );
		sinon.assert.calledWith( api.setAliases, 'Q1', 0, [ 'new alias' ], [ 'old alias' ], 'language' );
	} );

	QUnit.test( 'save performs correct API calls for removed aliases', function( assert ) {
		assert.expect( 2 );
		var api = {
			setAliases: sinon.spy( function() {
				return $.Deferred().promise();
			} )
		};
		var entityTermsChanger = new SUBJECT(
			api,
			{ getAliasesRevision: function() { return 0; } },
			new wb.datamodel.Item( 'Q1' )
		);

		entityTermsChanger.save(
			new wb.datamodel.Fingerprint(),
			new wb.datamodel.Fingerprint(
				null,
				null,
				new wb.datamodel.MultiTermMap( {
					language: new wb.datamodel.MultiTerm( 'language', [ 'old alias' ] )
				} )
			)
		);

		assert.ok( api.setAliases.calledOnce );
		sinon.assert.calledWith( api.setAliases, 'Q1', 0, [], [ 'old alias' ], 'language' );
	} );

	QUnit.test( 'save correctly handles API response for aliases', function( assert ) {
		assert.expect( 1 );
		var api = {
			setAliases: sinon.spy( function() {
				return $.Deferred().resolve( {
					entity: {
						aliases: {
							language: [ {
								value: 'normalized alias'
							} ],
							lastrevid: 'lastrevid'
						}
					}
				} ).promise();
			} )
		};
		var entityTermsChanger = new SUBJECT(
			api,
			{ getAliasesRevision: function() { return 0; }, setAliasesRevision: function() {} },
			new wb.datamodel.Item( 'Q1' )
		);

		QUnit.stop();

		entityTermsChanger.save(
			new wb.datamodel.Fingerprint(
				null,
				null,
				new wb.datamodel.MultiTermMap( {
					language: new wb.datamodel.MultiTerm( 'language', [ 'alias' ] )
				} )
			),
			new wb.datamodel.Fingerprint()
		).done( function( savedFingerprint ) {
			QUnit.start();
			assert.deepEqual( savedFingerprint.getAliasesFor( 'language' ).getTexts(), [ 'normalized alias' ] );
		} )
		.fail( function() {
			QUnit.start();
			assert.ok( false, 'save failed' );
		} );
	} );

	QUnit.test( 'save correctly handles API failures for aliases', function( assert ) {
		assert.expect( 2 );
		var api = {
			setAliases: sinon.spy( function() {
				return $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise();
			} )
		};
		var entityTermsChanger = new SUBJECT(
			api,
			{ getAliasesRevision: function() { return 0; }, setAliasesRevision: function() {} },
			new wb.datamodel.Item( 'Q1' )
		);

		QUnit.stop();

		entityTermsChanger.save(
			new wb.datamodel.Fingerprint(
				null,
				null,
				new wb.datamodel.MultiTermMap( {
					language: new wb.datamodel.MultiTerm( 'language', [ 'alias' ] )
				} )
			),
			new wb.datamodel.Fingerprint()
		).done( function( savedFingerprint ) {
			QUnit.start();
			assert.ok( false, 'save should have failed' );
		} )
		.fail( function( error ) {
			QUnit.start();
			assert.ok( error instanceof wb.api.RepoApiError, 'save did not fail with a RepoApiError' );
			assert.equal( error.code, 'errorCode' );
		} );
	} );

} )( sinon, wikibase, jQuery );
