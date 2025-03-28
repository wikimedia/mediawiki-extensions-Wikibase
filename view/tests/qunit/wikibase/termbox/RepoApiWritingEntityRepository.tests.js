/**
 * @license GPL-2.0-or-later
 */
QUnit.module( 'wikibase.termbox.RepoApiWritingEntityRepository', function () {
	const RepoApiWritingEntityRepository = require(
		'../../../../resources/wikibase/termbox/RepoApiWritingEntityRepository.js' );

	QUnit.test( 'success', async ( assert ) => {
		const revisionId = 1234;
		const testEntity = {
			id: 'Q42',
			labels: {
				de: { language: 'de', value: 'Kartoffel' },
				en: { language: 'en', value: 'potato' }
			},
			descriptions: {},
			aliases: {},
			lastrevid: revisionId
		};

		const stubRepoApi = {
			editEntity( id, baseRevId, data ) {
				return Promise.resolve( { entity: testEntity, success: 1 } );
			}
		};
		sinon.spy( stubRepoApi, 'editEntity' );

		const newRevision = await new RepoApiWritingEntityRepository( stubRepoApi )
			.saveEntity( testEntity, revisionId );

		assert.true( stubRepoApi.editEntity.calledWith( testEntity.id, revisionId, testEntity ) );
		assert.deepEqual( newRevision, { entity: testEntity, revisionId: testEntity.lastrevid } );
	} );

	QUnit.test( 'success with tempuser redirect', async ( assert ) => {
		const targetUrl = 'https://wiki.example';
		const revisionId = 1234;
		const testEntity = {
			id: 'Q42',
			labels: {
				de: { language: 'de', value: 'Kartoffel' },
				en: { language: 'en', value: 'potato' }
			},
			descriptions: {},
			aliases: {},
			lastrevid: revisionId
		};

		const stubRepoApi = {
			editEntity( id, baseRevId, data ) {
				return Promise.resolve( {
					entity: testEntity,
					tempuserredirect: targetUrl,
					success: 1
				} );
			}
		};
		sinon.spy( stubRepoApi, 'editEntity' );

		const newRevision = await new RepoApiWritingEntityRepository( stubRepoApi )
			.saveEntity( testEntity, revisionId );

		assert.true( stubRepoApi.editEntity.calledWith( testEntity.id, revisionId, testEntity ) );
		assert.deepEqual( newRevision, {
			entity: testEntity,
			revisionId: testEntity.lastrevid,
			redirectUrl: targetUrl
		} );
	} );

	QUnit.test( 'request failed', async ( assert ) => {
		const repoApiError = 'some-error-code';
		const stubRepoApi = {
			editEntity( id, baseRevId, data ) {
				return Promise.reject( repoApiError );
			}
		};

		try {
			await new RepoApiWritingEntityRepository( stubRepoApi )
				.saveEntity( { }, 1234 );
			assert.true( false );
		} catch ( e ) {
			assert.strictEqual( e.message, repoApiError );
			assert.true( e.getContext() !== null );
		}
	} );

	QUnit.test( 'invalid RepoApi response', async ( assert ) => {
		const stubRepoApi = {
			editEntity( id, baseRevId, data ) {
				return Promise.resolve( { entity: { foo: 'bar' }, success: 1 } );
			}
		};

		try {
			await new RepoApiWritingEntityRepository( stubRepoApi )
				.saveEntity( { }, 1234 );
			assert.true( false );
		} catch ( e ) {
			assert.strictEqual( e.message, 'Error: invalid entity serialization' );
			assert.true( e.getContext() !== null );
		}
	} );
} );
