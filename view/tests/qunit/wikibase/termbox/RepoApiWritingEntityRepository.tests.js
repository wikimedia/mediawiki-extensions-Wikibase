/**
 * @license GPL-2.0-or-later
 */
( function ( sinon, QUnit ) {
	const RepoApiWritingEntityRepository = require(
		'../../../../resources/wikibase/termbox/RepoApiWritingEntityRepository.js' );

	QUnit.module( 'wikibase.termbox.RepoApiWritingEntityRepository' );

	QUnit.test( 'success', function ( assert ) {
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

		new RepoApiWritingEntityRepository( stubRepoApi )
			.saveEntity( testEntity, revisionId )
			.then( ( newRevision ) => {
				assert.true( stubRepoApi.editEntity.calledWith( testEntity.id, revisionId, testEntity ) );
				assert.deepEqual( newRevision, { entity: testEntity, revisionId: testEntity.lastrevid } );
			} );

	} );

	QUnit.test( 'request failed', function ( assert ) {
		const repoApiError = 'some-error-code';
		const stubRepoApi = {
			editEntity( id, baseRevId, data ) {
				return Promise.reject( repoApiError );
			}
		};

		new RepoApiWritingEntityRepository( stubRepoApi )
			.saveEntity( { }, 1234 )
			.then( () => { assert.true( false ); } )
			.catch( ( e ) => {
				assert.strictEqual( e.message, repoApiError );
				assert.true( e.getContext() !== null );
			} );
		assert.expect( 0 );
	} );

	QUnit.test( 'invalid RepoApi response', function ( assert ) {
		const stubRepoApi = {
			editEntity( id, baseRevId, data ) {
				return Promise.resolve( { entity: { foo: 'bar' }, success: 1 } );
			}
		};

		new RepoApiWritingEntityRepository( stubRepoApi )
			.saveEntity( { }, 1234 )
			.then( () => { assert.true( false ); } )
			.catch( ( e ) => {
				assert.strictEqual( e.message, 'Error: invalid entity serialization' );
				assert.true( e.getContext() !== null );
			} );
		assert.expect( 0 );
	} );
}( sinon, QUnit ) );
