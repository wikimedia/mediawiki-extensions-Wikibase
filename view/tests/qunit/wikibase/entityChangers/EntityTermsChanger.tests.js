/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
( function ( wb ) {
	'use strict';

	var datamodel = require( 'wikibase.datamodel' );

	QUnit.module( 'wikibase.entityChangers.EntityTermsChanger', QUnit.newMwEnvironment() );

	var EntityTermsChanger = wb.entityChangers.EntityTermsChanger;
	var Term = datamodel.Term;
	var Item = datamodel.Item;

	/**
	 * Syntactic sugar for readability
	 *
	 * @type {createFingerprint}
	 */
	var newFingerprint = createFingerprint;
	var currentFingerprint = createFingerprint;

	var REVISION_ID = 9;

	QUnit.test( 'is a function', ( assert ) => {
		assert.strictEqual(
			typeof EntityTermsChanger,
			'function',
			'is a function.'
		);
	} );

	QUnit.test( 'is a constructor', ( assert ) => {
		assert.true( new EntityTermsChanger() instanceof EntityTermsChanger );
	} );

	QUnit.test( 'save performs correct API calls for new label', ( assert ) => {
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
		).then( () => {
			assert.true( api.setLabel.calledOnce );
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

	QUnit.test( 'save performs correct API calls for changed label', ( assert ) => {
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
		).then( () => {
			assert.true( api.setLabel.calledOnce );
			sinon.assert.calledWith( api.setLabel, 'Q1', REVISION_ID, 'new label', 'some-lang' );
		} )
			.fail( failOnError( assert ) )
			.always( done );
	} );

	QUnit.test( 'save performs correct API calls for removed label', ( assert ) => {
		var done = assert.async();
		var api = {
			setLabel: sinon.spy( () => $.Deferred().resolve( {
				entity: {
					lastrevid: REVISION_ID,
					labels: {
						langCode: {
							language: 'langCode',
							removed: ''
						}
					}
				}
			} ).promise() )
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( REVISION_ID ),
			new Item( 'Q1' )
		);

		entityTermsChanger.save(
			newFingerprint().empty(),
			currentFingerprint().withLabel( 'langCode', 'old label' )
		).then( () => {
			assert.true( api.setLabel.calledOnce );
			sinon.assert.calledWith( api.setLabel, 'Q1', REVISION_ID, '', 'langCode' );
		} ).fail( failOnError( assert ) ).always( done );
	} );

	QUnit.test( 'save correctly handles API response for labels', ( assert ) => {
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
		).done( ( entityTermChangeResult ) => {
			assert.strictEqual(
				entityTermChangeResult.getSavedValue().getLabelFor( 'language' ).getText(),
				'normalized label'
			);
		} );
	} );

	QUnit.test( 'save correctly handles API failures for labels', ( assert ) => {
		var done = assert.async();
		var api = {
			setLabel: sinon.spy( () => $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise() )
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( REVISION_ID ),
			new Item( 'Q1' )
		);

		entityTermsChanger.save(
			newFingerprint().withLabel( 'language', 'label' ),
			currentFingerprint().empty()
		).done( ( savedFingerprint ) => {
			assert.true( false, 'save should have failed' );
		} )
		.fail( ( error ) => {
			assert.true( error instanceof wb.api.RepoApiError, 'save did not fail with a RepoApiError' );
			assert.strictEqual( error.code, 'errorCode' );
			assert.strictEqual( error.context.type, 'label' );
			assert.true( error.context.value.equals( new Term( 'language', 'label' ) ) );
		} )
		.always( done );
	} );

	QUnit.test( 'save performs correct API calls for new description', ( assert ) => {
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
		).then( () => {
			assert.true( api.setDescription.calledOnce );
			sinon.assert.calledWith(
				api.setDescription,
				'Q1',
				revisionId,
				'description',
				'some-lang'
			);
		} ).fail( failOnError( assert ) ).always( done );
	} );

	QUnit.test( 'save performs correct API calls for changed description', ( assert ) => {
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
		).then( () => {
			assert.true( api.setDescription.calledOnce );
			sinon.assert.calledWith(
				api.setDescription,
				'Q1',
				REVISION_ID,
				'new description',
				'some-lang'
			);
		} ).fail( failOnError( assert ) ).always( done );
	} );

	QUnit.test( 'save performs correct API calls for removed description', ( assert ) => {
		var done = assert.async();
		var api = {
			setDescription: sinon.spy( () => $.Deferred().resolve( {
				entity: {
					descriptions: {
						langCode: {
							language: 'langCode',
							removed: ''
						}
					}
				}
			} ).promise() )
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( REVISION_ID ),
			new Item( 'Q1' )
		);

		entityTermsChanger.save(
			newFingerprint().empty(),
			currentFingerprint().withDescription( 'langCode', 'old description' )
		).then( () => {
			assert.true( api.setDescription.calledOnce );
			sinon.assert.calledWith( api.setDescription, 'Q1', REVISION_ID, '', 'langCode' );
		} ).fail( failOnError( assert ) ).always( done );
	} );

	QUnit.test( 'save correctly handles API response for descriptions', ( assert ) => {
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
		).done( ( entityTermChangeResult ) => {
			assert.strictEqual(
				entityTermChangeResult.getSavedValue().getDescriptionFor( 'language' ).getText(),
				'normalized description'
			);
		} ).fail( failOnError( assert ) ).always( done );
	} );

	QUnit.test( 'save correctly handles API failures for descriptions', ( assert ) => {
		var api = {
			setDescription: sinon.spy( () => $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise() )
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
		).done( ( savedFingerprint ) => {
			assert.true( false, 'save should have failed' );
		} )
		.fail( ( error ) => {
			assert.true( error instanceof wb.api.RepoApiError, 'save did not fail with a RepoApiError' );
			assert.strictEqual( error.code, 'errorCode' );
			assert.strictEqual( error.context.type, 'description' );
			assert.true( error.context.value.equals( new Term( 'language', 'description' ) ) );
		} ).always( done );
	} );

	QUnit.test( 'save performs correct API calls for new aliases', ( assert ) => {
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
		).then( () => {
			assert.true( api.setAliases.calledOnce );
			sinon.assert.calledWith( api.setAliases, 'Q1', revisionId, [ 'alias' ], [], 'language' );
		} ).fail( failOnError( assert ) ).always( done );
	} );

	QUnit.test( 'save performs correct API calls for changed aliases', ( assert ) => {
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
		).then( () => {
			assert.true( api.setAliases.calledOnce );
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

	QUnit.test( 'save performs correct API calls for removed aliases', ( assert ) => {
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
		).then( () => {
			assert.true( api.setAliases.calledOnce );
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

	QUnit.test( 'save correctly handles API response for aliases', ( assert ) => {
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
		).done( ( entityTermChangeResult ) => {
			assert.deepEqual(
				entityTermChangeResult.getSavedValue().getAliasesFor( 'language' ).getTexts(),
				[ 'normalized alias' ]
			);
		} ).fail( failOnError( assert ) ).always( done );
	} );

	QUnit.test( 'save correctly handles API failures for aliases', ( assert ) => {
		var api = {
			setAliases: sinon.spy( () => $.Deferred().reject( 'errorCode', { error: { code: 'errorCode' } } ).promise() )
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
		).done( ( savedFingerprint ) => {
			assert.true( false, 'save should have failed' );
		} )
		.fail( ( error ) => {
			assert.true( error instanceof wb.api.RepoApiError, 'save did not fail with a RepoApiError' );
			assert.strictEqual( error.code, 'errorCode' );
			assert.strictEqual( error.context.type, 'aliases' );
			assert.true( error.context.value.equals( new datamodel.MultiTerm( 'language', [ 'alias' ] ) ) );
		} ).always( done );
	} );

	QUnit.test( 'save tracks temp user redirect url if supplied', ( assert ) => {
		const target = 'https://wiki.example';
		var done = assert.async();
		var api = {
			setDescription: sinon.spy( () => $.Deferred().resolve( {
				entity: {
					descriptions: {
						langCode: {
							language: 'langCode',
							removed: ''
						}
					}
				},
				tempusercreated: 'SomeUser',
				tempuserredirect: target
			} ).promise() )
		};
		var entityTermsChanger = new EntityTermsChanger(
			api,
			stubRevisionStoreForRevision( REVISION_ID ),
			new Item( 'Q1' )
		);

		return entityTermsChanger.save(
			newFingerprint().empty(),
			currentFingerprint().withDescription( 'langCode', 'old description' )
		).done( ( entityTermChangeResult ) => {
			assert.true( true, 'save succeeded' );
			assert.strictEqual(
				target,
				entityTermChangeResult.getTempUserWatcher().getRedirectUrl(),
				'redirect url should be set'
			);
		} ).always( done );
	} );

	function failOnError( assert ) {
		return function ( error ) {
			assert.true( false, error.stack || error );
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
		function ApiResponse( revId ) {
			this.entity = {
				lastrevid: revId
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
			this.entity.aliases[ language ] = aliases.map( ( alias ) => ( { value: alias } ) );
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
			datamodel.Fingerprint.call( this );
		}

		$.extend( FingerprintBuilder.prototype, datamodel.Fingerprint.prototype );

		FingerprintBuilder.prototype.withLabel = function withLabel( language, value ) {
			this.setLabel( language, new Term( language, value ) );
			return this;
		};

		FingerprintBuilder.prototype.withDescription = function withDescription( language, value ) {
			this.setDescription( language, new Term( language, value ) );
			return this;
		};

		FingerprintBuilder.prototype.withAliases = function withDescription( language, aliases ) {
			this.setAliases( language, new datamodel.MultiTerm( language, aliases ) );
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
	 * @param {number} revisionId
	 * @return {SuccessfulCallbackBuilder}
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

}( wikibase ) );
