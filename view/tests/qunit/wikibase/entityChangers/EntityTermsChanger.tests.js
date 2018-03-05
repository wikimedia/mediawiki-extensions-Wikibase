/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( sinon, wb, $ ) {
	'use strict';

	QUnit.module( 'wikibase.entityChangers.EntityTermsChanger', QUnit.newMwEnvironment() );

	var EntityTermsChanger = wb.entityChangers.EntityTermsChanger;
	var Term = wb.datamodel.Term;
	var Item = wb.datamodel.Item;

	/**
	 * Syntactic sugar for readability
	 * @type {createFingerprint}
	 */
	var newFingerprint = createFingerprint;
	var currentFingerprint = createFingerprint;

	var REVISION_ID = 9;

	QUnit.test( 'is a function', function ( assert ) {
		assert.expect( 1 );
		assert.equal(
			typeof EntityTermsChanger,
			'function',
			'is a function.'
		);
	} );

	QUnit.test( 'is a constructor', function ( assert ) {
		assert.expect( 1 );
		assert.ok( new EntityTermsChanger() instanceof EntityTermsChanger );
	} );

	QUnit.test( 'save performs correct API calls for new label', function ( assert ) {
		assert.expect( 2 );
		var done = assert.async();
		var api = {
			setLabel: sinon.spy(
				functionReturningSuccessfulResponse( REVISION_ID )
					.withLabel( 'some-lang', 'some label' )
			)
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( REVISION_ID ),
			new Item( 'Q1' )
		);

		entityTermsChanger.save(
			newFingerprint().withLabel( 'some-lang', 'some label' ),
			currentFingerprint().empty()
		).then( function () {
			assert.ok( api.setLabel.calledOnce );
			sinon.assert.calledWith(
				api.setLabel,
				'Q1',
				REVISION_ID,
				'some label',
				'some-lang'
			);
		} )
			.fail( failOnError( assert ) )
			.always( done );
	} );

	QUnit.test( 'save performs correct API calls for changed label', function ( assert ) {
		assert.expect( 2 );
		var done = assert.async();
		var api = {
			setLabel: sinon.spy( functionReturningSuccessfulResponse( REVISION_ID )
					.withLabel( 'some-lang', 'new label' ) )
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( REVISION_ID ),
			new Item( 'Q1' )
		);

		entityTermsChanger.save(
			newFingerprint().withLabel( 'some-lang', 'new label' ),
			currentFingerprint().withLabel( 'some-lang', 'old label' )
		).then( function () {
			assert.ok( api.setLabel.calledOnce );
			sinon.assert.calledWith( api.setLabel, 'Q1', REVISION_ID, 'new label', 'some-lang' );
		} )
			.fail( failOnError( assert ) )
			.always( done );
	} );

	QUnit.test( 'save performs correct API calls for removed label', function ( assert ) {
		assert.expect( 2 );
		var done = assert.async();
		var api = {
			setLabel: sinon.spy( function () {
				return $.Deferred().resolve( {
					entity: {
						lastrevid: REVISION_ID,
						labels: {
							langCode: {
								language: 'langCode',
								removed: ''
							}
						}
					}
				} ).promise();
			} )
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( REVISION_ID ),
			new Item( 'Q1' )
		);

		entityTermsChanger.save(
			newFingerprint().empty(),
			currentFingerprint().withLabel( 'langCode', 'old label' )
		).then( function () {
			assert.ok( api.setLabel.calledOnce );
			sinon.assert.calledWith( api.setLabel, 'Q1', REVISION_ID, '', 'langCode' );
		} ).fail( failOnError( assert ) ).always( done );
	} );

	QUnit.test( 'save correctly handles API response for labels', function ( assert ) {
		assert.expect( 1 );
		var api = {
			setLabel: sinon.spy(
				functionReturningSuccessfulResponse( 'lastrevid' )
					.withLabel( 'language', 'normalized label' )
			)
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( REVISION_ID ),
			new Item( 'Q1' )
		);

		return entityTermsChanger.save(
			newFingerprint().withLabel( 'language', 'label' ),
			currentFingerprint().empty()
		).done( function ( savedFingerprint ) {
			assert.equal( savedFingerprint.getLabelFor( 'language' ).getText(), 'normalized label' );
		} );
	} );

	QUnit.test( 'save correctly handles API failures for labels', function ( assert ) {
		var done = assert.async();
		assert.expect( 4 );
		var api = {
			setLabel: sinon.spy( function () {
				return $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise();
			} )
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( REVISION_ID ),
			new Item( 'Q1' )
		);

		entityTermsChanger.save(
			newFingerprint().withLabel( 'language', 'label' ),
			currentFingerprint().empty()
		).done( function ( savedFingerprint ) {
			assert.ok( false, 'save should have failed' );
		} )
		.fail( function ( error ) {
			assert.ok( error instanceof wb.api.RepoApiError, 'save did not fail with a RepoApiError' );
			assert.equal( error.code, 'errorCode' );
			assert.equal( error.context.type, 'label' );
			assert.ok( error.context.value.equals( new Term( 'language', 'label' ) ) );
		} )
		.always( done );
	} );

	QUnit.test( 'save performs correct API calls for new description', function ( assert ) {
		assert.expect( 2 );
		var done = assert.async();
		var revisionId = 9;
		var api = {
			setDescription: sinon.spy(
				functionReturningSuccessfulResponse( REVISION_ID )
					.withDescription( 'some-lang', 'description' )
			)
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( revisionId ),
			new Item( 'Q1' )
		);

		entityTermsChanger.save(
			newFingerprint().withDescription( 'some-lang', 'description' ),
			currentFingerprint().empty()
		).then( function () {
			assert.ok( api.setDescription.calledOnce );
			sinon.assert.calledWith(
				api.setDescription,
				'Q1',
				revisionId,
				'description',
				'some-lang'
			);
		} ).fail( failOnError( assert ) ).always( done );
	} );

	QUnit.test( 'save performs correct API calls for changed description', function ( assert ) {
		assert.expect( 2 );
		var done = assert.async();

		var api = {
			setDescription: sinon.spy(
				functionReturningSuccessfulResponse( REVISION_ID )
					.withDescription( 'some-lang', 'new description' )
			)
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( REVISION_ID ),
			new Item( 'Q1' )
		);

		entityTermsChanger.save(
			newFingerprint().withDescription( 'some-lang', 'new description' ),
			currentFingerprint().withDescription( 'some-lang', 'old description' )
		).then( function () {
			assert.ok( api.setDescription.calledOnce );
			sinon.assert.calledWith(
				api.setDescription,
				'Q1',
				REVISION_ID,
				'new description',
				'some-lang'
			);
		} ).fail( failOnError( assert ) ).always( done );
	} );

	QUnit.test( 'save performs correct API calls for removed description', function ( assert ) {
		assert.expect( 2 );
		var done = assert.async();
		var api = {
			setDescription: sinon.spy( function () {
				return $.Deferred().resolve( {
					entity: {
						descriptions: {
							langCode: {
								language: 'langCode',
								removed: ''
							}
						}
					}
				} ).promise();
			} )
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( REVISION_ID ),
			new Item( 'Q1' )
		);

		entityTermsChanger.save(
			newFingerprint().empty(),
			currentFingerprint().withDescription( 'langCode', 'old description' )
		).then( function () {
			assert.ok( api.setDescription.calledOnce );
			sinon.assert.calledWith( api.setDescription, 'Q1', REVISION_ID, '', 'langCode' );
		} ).fail( failOnError( assert ) ).always( done );
	} );

	QUnit.test( 'save correctly handles API response for descriptions', function ( assert ) {
		assert.expect( 1 );
		var api = {
			setDescription: sinon.spy(
				functionReturningSuccessfulResponse( 'lastrevid' )
					.withDescription( 'language', 'normalized description' )
			)
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( REVISION_ID ),
			new Item( 'Q1' )
		);

		var done = assert.async();

		entityTermsChanger.save(
			newFingerprint().withDescription( 'language', 'description' ),
			currentFingerprint().empty()
		).done( function ( savedFingerprint ) {
			assert.equal( savedFingerprint.getDescriptionFor( 'language' ).getText(), 'normalized description' );
		} ).fail( failOnError( assert ) ).always( done );
	} );

	QUnit.test( 'save correctly handles API failures for descriptions', function ( assert ) {
		assert.expect( 4 );
		var api = {
			setDescription: sinon.spy( function () {
				return $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise();
			} )
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( REVISION_ID ),
			new Item( 'Q1' )
		);

		var done = assert.async();

		entityTermsChanger.save(
			newFingerprint().withDescription( 'language', 'description' ),
			currentFingerprint().empty()
		).done( function ( savedFingerprint ) {
			assert.ok( false, 'save should have failed' );
		} )
		.fail( function ( error ) {
			assert.ok( error instanceof wb.api.RepoApiError, 'save did not fail with a RepoApiError' );
			assert.equal( error.code, 'errorCode' );
			assert.equal( error.context.type, 'description' );
			assert.ok( error.context.value.equals( new Term( 'language', 'description' ) ) );
		} ).always( done );
	} );

	QUnit.test( 'save performs correct API calls for new aliases', function ( assert ) {
		assert.expect( 2 );
		var revisionId = REVISION_ID;
		var done = assert.async();
		var api = {
			setAliases: sinon.spy(
				functionReturningSuccessfulResponse( revisionId )
					.withAliases( 'language', [ 'alias' ] )
			)
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( revisionId ),
			new Item( 'Q1' )
		);

		entityTermsChanger.save(
			newFingerprint().withAliases( 'language', [ 'alias' ] ),
			currentFingerprint().empty()
		).then( function () {
			assert.ok( api.setAliases.calledOnce );
			sinon.assert.calledWith( api.setAliases, 'Q1', revisionId, [ 'alias' ], [], 'language' );
		} ).fail( failOnError( assert ) ).always( done );
	} );

	QUnit.test( 'save performs correct API calls for changed aliases', function ( assert ) {
		assert.expect( 2 );
		var done = assert.async();
		var api = {
			setAliases: sinon.spy(
				functionReturningSuccessfulResponse( REVISION_ID )
					.withAliases( 'language', [ 'new alias' ] )
			)
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( REVISION_ID ),
			new Item( 'Q1' )
		);

		entityTermsChanger.save(
			newFingerprint().withAliases( 'language', [ 'new alias' ] ),
			currentFingerprint().withAliases( 'language', [ 'old alias' ] )
		).then( function () {
			assert.ok( api.setAliases.calledOnce );
			sinon.assert.calledWith(
				api.setAliases,
				'Q1',
				REVISION_ID,
				[ 'new alias' ],
				[ 'old alias' ],
				'language'
			);
		} ).fail( failOnError( assert ) ).always( done );
	} );

	QUnit.test( 'save performs correct API calls for removed aliases', function ( assert ) {
		assert.expect( 2 );
		var done = assert.async();
		var api = {
			setAliases: sinon.spy( functionReturningSuccessfulResponse( REVISION_ID ) )
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( REVISION_ID ),
			new Item( 'Q1' )
		);

		entityTermsChanger.save(
			newFingerprint().empty(),
			currentFingerprint().withAliases( 'language', [ 'old alias' ] )
		).then( function () {
			assert.ok( api.setAliases.calledOnce );
			sinon.assert.calledWith(
				api.setAliases,
				'Q1',
				REVISION_ID,
				[],
				[ 'old alias' ],
				'language'
			);
		} ).fail( failOnError( assert ) ).always( done );
	} );

	QUnit.test( 'save correctly handles API response for aliases', function ( assert ) {
		assert.expect( 1 );
		var done = assert.async();
		var api = {
			setAliases: sinon.spy(
				functionReturningSuccessfulResponse( 'lastrevid' )
					.withAliases( 'language', [ 'normalized alias' ] )
			)
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( 'lastrevid' ),
			new Item( 'Q1' )
		);

		return entityTermsChanger.save(
			newFingerprint().withAliases( 'language', [ 'alias' ] ),
			currentFingerprint().empty()
		).done( function ( savedFingerprint ) {
			assert.deepEqual( savedFingerprint.getAliasesFor( 'language' ).getTexts(), [ 'normalized alias' ] );
		} ).fail( failOnError( assert ) ).always( done );
	} );

	QUnit.test( 'save correctly handles API failures for aliases', function ( assert ) {
		assert.expect( 4 );
		var api = {
			setAliases: sinon.spy( function () {
				return $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise();
			} )
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( REVISION_ID ),
			new Item( 'Q1' )
		);

		var done = assert.async();

		entityTermsChanger.save(
			newFingerprint().withAliases( 'language', [ 'alias' ] ),
			currentFingerprint().empty()
		).done( function ( savedFingerprint ) {
			assert.ok( false, 'save should have failed' );
		} )
		.fail( function ( error ) {
			assert.ok( error instanceof wb.api.RepoApiError, 'save did not fail with a RepoApiError' );
			assert.equal( error.code, 'errorCode' );
			assert.equal( error.context.type, 'aliases' );
			assert.ok( error.context.value.equals( new wb.datamodel.MultiTerm( 'language', [ 'alias' ] ) ) );
		} ).always( done );
	} );

	function failOnError( assert ) {
		return function ( error ) {
			assert.ok( false, error.stack || error );
		};
	}

	function stubRevisionStoreForRevision( revisionId ) {
		return {
			getLabelRevision: function () {
				return revisionId;
			},
			setLabelRevision: function () {
			},
			getDescriptionRevision: function () {
				return revisionId;
			},
			setDescriptionRevision: function () {
			},
			getAliasesRevision: function () {
				return revisionId;
			},
			setAliasesRevision: function () {
			}
		};
	}

	function apiResponseForRevision( revisionId ) {
		function ApiResponse( revisionId ) {
			this.entity = {
				lastrevid: revisionId
			};
		}

		ApiResponse.prototype.withLabel = function ( language, value ) {
			if ( !this.entity.labels ) {
				this.entity.labels = {};
			}
			this.entity.labels[ language ] = { value: value };
			return this;
		};

		ApiResponse.prototype.withDescription = function ( language, value ) {
			if ( !this.entity.descriptions ) {
				this.entity.descriptions = {};
			}
			this.entity.descriptions[ language ] = { value: value };
			return this;
		};

		ApiResponse.prototype.withAliases = function ( language, aliases ) {
			if ( !this.entity.aliases ) {
				this.entity.aliases = {};
			}
			this.entity.aliases[ language ] = aliases.map( function ( alias ) {
				return { value: alias };
			} );
			return this;
		};

		return new ApiResponse( revisionId );
	}

	/**
	 * @return {FingerprintBuilder}
	 */
	function createFingerprint() {
		/**
		 * @class FingerprintBuilder
		 * @constructor
		 */
		function FingerprintBuilder() {
			wb.datamodel.Fingerprint.call( this );
		}

		jQuery.extend( FingerprintBuilder.prototype, wb.datamodel.Fingerprint.prototype );

		FingerprintBuilder.prototype.withLabel = function withLabel( language, value ) {
			this.setLabel( language, new Term( language, value ) );
			return this;
		};

		FingerprintBuilder.prototype.withDescription = function withDescription( language, value ) {
			this.setDescription( language, new Term( language, value ) );
			return this;
		};

		FingerprintBuilder.prototype.withAliases = function withDescription( language, aliases ) {
			this.setAliases( language, new wb.datamodel.MultiTerm( language, aliases ) );
			return this;
		};

		/**
		 * Syntactic sugar for readability
		 *
		 * @return {FingerprintBuilder}
		 */
		FingerprintBuilder.prototype.empty = function empty() {
			return this;
		};

		return new FingerprintBuilder();
	}

	/**
	 * @param revisionId
	 * @returns {SuccessfulCallbackBuilder}
	 */
	function functionReturningSuccessfulResponse( revisionId ) {
		var result = apiResponseForRevision( revisionId );
		/**
		 * @class SuccessfulCallbackBuilder
		 */
		var callback = function () {
			return $.Deferred().resolve( result ).promise();
		};

		callback.withLabel = function callbackWithLabel( language, value ) {
			result.withLabel( language, value );
			return callback;
		};

		callback.withDescription = function callbackWithDescription( language, value ) {
			result.withDescription( language, value );
			return callback;
		};

		callback.withAliases = function callbackWithAliases( language, aliases ) {
			result.withAliases( language, aliases );
			return callback;
		};

		return callback;
	}

}( sinon, wikibase, jQuery ) );
