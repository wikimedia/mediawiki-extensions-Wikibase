import ForeignApiWritingRepository from '@/data-access/ForeignApiWritingRepository';
import { ForeignApi } from '@/@types/mediawiki/MwWindow';
import EntityNotFound from '@/data-access/error/EntityNotFound';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import EntityRevision from '@/datamodel/EntityRevision';
import Entity from '@/datamodel/Entity';

function mockForeignApi( successObject?: unknown, rejectData?: unknown ): ForeignApi {
	return {
		postWithEditToken: (): any => {
			if ( successObject ) {
				return Promise.resolve( successObject );
			}

			if ( rejectData ) {
				return Promise.reject( rejectData );
			}

			return Promise.resolve( {
				success: 1,
				entity: {
					id: 'Q0',
					claims: {},
					lastrevid: -1,
				},
			} );
		},
	} as any;
}

describe( 'ForeignApiWritingRepository', () => {
	it( 'returns well formatted answer as expected', () => {
		const response = {
			entity: {
				lastrevid: 2183,
				type: 'item',
				id: 'Q123',
				labels: { en: { language: 'en', value: 'Wikidata bridge test item' } },
				descriptions: [],
				aliases: [],
				claims: {
					P20: [ {
						mainsnak: {
							snaktype: 'value',
							property: 'P20',
							datavalue: {
								value: 'String for Wikidata bridge',
								type: 'string',
							},
							datatype: 'string',
						},
						type: 'statement',
						id: 'Q123$36ae6854-4e74-d74c-d583-701bc130166f',
						rank: 'normal',
					} ],
				},
				sitelinks: [],
			} as any,
			success: 1,
		};

		const foreignApi = mockForeignApi( response );

		const entityWriter = new ForeignApiWritingRepository( foreignApi, 'user' );
		const toBeWrittenEntity = {
			revisionId: 123,
			entity: {
				id: 'Q123',
				labels: { de: { language: 'de', value: 'test' } },
				statements: {},
			},
		};

		const expected = new EntityRevision(
			new Entity( response.entity.id, response.entity.claims ),
			response.entity.lastrevid,
		);

		return entityWriter.saveEntity( toBeWrittenEntity )
			.then( ( entity ) => {
				expect( entity ).toStrictEqual( expected );
			} );
	} );

	it( 'delagates the change to the foreignApi', () => {
		const foreignApi = mockForeignApi();
		jest.spyOn( foreignApi, 'postWithEditToken' );
		const toBeWrittenEntity = {
			revisionId: 123,
			entity: {
				id: 'Q123',
				statements: {
					P20: [ {
						mainsnak: {
							snaktype: 'value',
							property: 'P20',
							datavalue: {
								value: 'String for Wikidata bridge',
								type: 'string',
							},
							datatype: 'string',
						},
						type: 'statement',
						id: 'Q123$36ae6854-4e74-d74c-d583-701bc130166f',
						rank: 'normal',
					} ],
				} as any,
			},
		};

		const assertuser = 'sampleUser';
		const entityWriter = new ForeignApiWritingRepository( foreignApi, assertuser );

		return entityWriter.saveEntity( toBeWrittenEntity )
			.then( () => {
				expect( foreignApi.postWithEditToken ).toHaveBeenCalledTimes( 1 );
				expect( foreignApi.postWithEditToken ).toHaveBeenCalledWith( {
					action: 'wbeditentity',
					assertuser,
					baserevid: toBeWrittenEntity.revisionId,
					id: toBeWrittenEntity.entity.id,
					data: JSON.stringify( {
						claims: toBeWrittenEntity.entity.statements,
					} ),
				} );
			} );
	} );

	it( 'delegates anonymous user to the foreignApi', () => {
		const foreignApi = mockForeignApi();
		jest.spyOn( foreignApi, 'postWithEditToken' );
		const toBeWrittenEntity = {
			revisionId: 123,
			entity: {
				id: 'Q123',
				statements: {
					P20: [ {
						mainsnak: {
							snaktype: 'value',
							property: 'P20',
							datavalue: {
								value: 'String for Wikidata bridge',
								type: 'string',
							},
							datatype: 'string',
						},
						type: 'statement',
						id: 'Q123$36ae6854-4e74-d74c-d583-701bc130166f',
						rank: 'normal',
					} ],
				} as any,
			},
		};

		const assertuser = null;
		const entityWriter = new ForeignApiWritingRepository( foreignApi, assertuser );

		return entityWriter.saveEntity( toBeWrittenEntity )
			.then( () => {
				expect( foreignApi.postWithEditToken ).toHaveBeenCalledTimes( 1 );
				expect( foreignApi.postWithEditToken ).toHaveBeenCalledWith( {
					action: 'wbeditentity',
					assertuser: undefined,
					baserevid: toBeWrittenEntity.revisionId,
					id: toBeWrittenEntity.entity.id,
					data: JSON.stringify( {
						claims: toBeWrittenEntity.entity.statements,
					} ),
				} );
			} );
	} );

	it( 'can delagate tags to the foreignApi', () => {
		const foreignApi = mockForeignApi();
		jest.spyOn( foreignApi, 'postWithEditToken' );
		const toBeWrittenEntity = {
			revisionId: 123,
			entity: {
				id: 'Q123',
				statements: {
					P20: [ {
						mainsnak: {
							snaktype: 'value',
							property: 'P20',
							datavalue: {
								value: 'String for Wikidata bridge',
								type: 'string',
							},
							datatype: 'string',
						},
						type: 'statement',
						id: 'Q123$36ae6854-4e74-d74c-d583-701bc130166f',
						rank: 'normal',
					} ],
				} as any,
			},
		};

		const assertuser = 'sampleUser';
		const tags = [ 'tag1', 'tag2', 'tag3' ];
		const entityWriter = new ForeignApiWritingRepository( foreignApi, assertuser, tags );

		return entityWriter.saveEntity( toBeWrittenEntity )
			.then( () => {
				expect( foreignApi.postWithEditToken ).toHaveBeenCalledTimes( 1 );
				expect( foreignApi.postWithEditToken ).toHaveBeenCalledWith( {
					action: 'wbeditentity',
					assertuser,
					baserevid: toBeWrittenEntity.revisionId,
					id: toBeWrittenEntity.entity.id,
					tags,
					data: JSON.stringify( {
						claims: toBeWrittenEntity.entity.statements,
					} ),
				} );
			} );
	} );

	describe( 'if there is a problem', () => {
		const toBeWrittenEntity = {
			revisionId: 123,
			entity: {
				id: 'Q123',
				labels: { de: { language: 'de', value: 'test' } },
				statements: {},
			},
		};

		it( 'rejects on result that does not contain an object', () => {
			const mock = mockForeignApi( 'noObject' );

			const entityWriter = new ForeignApiWritingRepository( mock, 'user' );
			return expect( entityWriter.saveEntity( toBeWrittenEntity ) )
				.rejects
				.toStrictEqual( new TechnicalProblem( 'unknown response type.' ) );
		} );

		it( 'rejects on exsiting error field', () => {
			const error = {
				error: {
					code: 'test-error',
				},
			};

			const mock = mockForeignApi( error );

			const entityWriter = new ForeignApiWritingRepository( mock, 'nuterin' );
			return expect( entityWriter.saveEntity( toBeWrittenEntity ) )
				.rejects
				.toStrictEqual( new TechnicalProblem( error.error.code ) );
		} );

		it( 'rejects on result indicating relevant entity as missing', () => {
			const mock = mockForeignApi( null, { status: 404 } );

			const entityWriter = new ForeignApiWritingRepository( mock, 'user' );
			return expect( entityWriter.saveEntity( toBeWrittenEntity ) )
				.rejects
				.toStrictEqual( new EntityNotFound( 'The given api page does not exist.' ) );
		} );

		it( 'rejects if there was a serverside problem with the API', () => {
			const mock = mockForeignApi( null, { status: 500 } );

			const entityWriter = new ForeignApiWritingRepository( mock, 'user' );
			return expect( entityWriter.saveEntity( toBeWrittenEntity ) )
				.rejects
				.toStrictEqual( new JQueryTechnicalError( { status: 500 } as any ) );
		} );
	} );
} );
