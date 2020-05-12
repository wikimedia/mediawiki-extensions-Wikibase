import ApiWritingRepository from '@/data-access/ApiWritingRepository';
import EntityRevision from '@/datamodel/EntityRevision';
import Entity from '@/datamodel/Entity';
import { mockApi } from '../../util/mocks';
import ApiErrors from '@/data-access/error/ApiErrors';
import SavingError from '@/data-access/error/SavingError';
import { ErrorTypes } from '@/definitions/ApplicationError';

describe( 'ApiWritingRepository', () => {
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

		const api = mockApi( response );

		const entityWriter = new ApiWritingRepository( api );
		const toBeWrittenEntity = {
			id: 'Q123',
			labels: { de: { language: 'de', value: 'test' } },
			statements: {},
		};
		const baseEntityRevision = {
			revisionId: 123,
			entity: {} as Entity,
		};

		const expected = new EntityRevision(
			new Entity( response.entity.id, response.entity.claims ),
			response.entity.lastrevid,
		);

		return entityWriter.saveEntity( toBeWrittenEntity, baseEntityRevision )
			.then( ( entity ) => {
				expect( entity ).toStrictEqual( expected );
			} );
	} );

	it( 'delegates the change to the api', () => {
		const api = mockApi( {
			success: 1,
			entity: {
				id: 'Q0',
				claims: {},
				lastrevid: -1,
			},
		} );
		jest.spyOn( api, 'postWithEditTokenAndAssertUser' );
		const toBeWrittenEntity = {
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
		};
		const baseRevision = {
			revisionId: 123,
			entity: {} as Entity,
		};
		const expectedParams = {
			action: 'wbeditentity',
			baserevid: baseRevision.revisionId,
			id: toBeWrittenEntity.id,
			data: JSON.stringify( {
				claims: toBeWrittenEntity.statements,
			} ),
		};

		const entityWriter = new ApiWritingRepository( api );

		return entityWriter.saveEntity( toBeWrittenEntity, baseRevision )
			.then( () => {
				expect( api.postWithEditTokenAndAssertUser ).toHaveBeenCalledTimes( 1 );
				expect( api.postWithEditTokenAndAssertUser ).toHaveBeenCalledWith( expectedParams );
			} );
	} );

	it( 'can delegate tags to the api', () => {
		const api = mockApi( {
			success: 1,
			entity: {
				id: 'Q0',
				claims: {},
				lastrevid: -1,
			},
		} );
		jest.spyOn( api, 'postWithEditTokenAndAssertUser' );
		const toBeWrittenEntity = {
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
		};
		const baseRevision = {
			revisionId: 123,
			entity: {} as Entity,
		};

		const tags = [ 'tag1', 'tag2', 'tag3' ];
		const entityWriter = new ApiWritingRepository( api, tags );

		return entityWriter.saveEntity( toBeWrittenEntity, baseRevision )
			.then( () => {
				expect( api.postWithEditTokenAndAssertUser ).toHaveBeenCalledTimes( 1 );
				expect( api.postWithEditTokenAndAssertUser ).toHaveBeenCalledWith( {
					action: 'wbeditentity',
					baserevid: baseRevision.revisionId,
					id: toBeWrittenEntity.id,
					tags,
					data: JSON.stringify( {
						claims: toBeWrittenEntity.statements,
					} ),
				} );
			} );
	} );

	it( 'supports saving without a base revision', () => {
		const api = mockApi( {
			success: 1,
			entity: {
				id: 'Q0',
				claims: {},
				lastrevid: -1,
			},
		} );
		jest.spyOn( api, 'postWithEditToken' );
		jest.spyOn( api, 'postWithEditTokenAndAssertUser' );
		const toBeWrittenEntity = {
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
		};
		const expectedParams = {
			action: 'wbeditentity',
			id: toBeWrittenEntity.id,
			data: JSON.stringify( {
				claims: toBeWrittenEntity.statements,
			} ),
		};

		const entityWriter = new ApiWritingRepository( api );

		return entityWriter.saveEntity( toBeWrittenEntity )
			.then( () => {
				expect( api.postWithEditTokenAndAssertUser ).toHaveBeenCalledTimes( 1 );
				expect( api.postWithEditTokenAndAssertUser ).toHaveBeenCalledWith( expectedParams );
			} );
	} );

	it( 'saves and asserts user by default', () => {
		const api = mockApi( {
			success: 1,
			entity: {
				id: 'Q0',
				claims: {},
				lastrevid: -1,
			},
		} );
		jest.spyOn( api, 'postWithEditToken' );
		jest.spyOn( api, 'postWithEditTokenAndAssertUser' );
		const toBeWrittenEntity = {
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
		};

		const entityWriter = new ApiWritingRepository( api );

		return entityWriter.saveEntity( toBeWrittenEntity )
			.then( () => {
				expect( api.postWithEditTokenAndAssertUser ).toHaveBeenCalledTimes( 1 );
			} );
	} );

	it( 'can save without asserting the user', () => {
		const api = mockApi( {
			success: 1,
			entity: {
				id: 'Q0',
				claims: {},
				lastrevid: -1,
			},
		} );
		jest.spyOn( api, 'postWithEditToken' );
		jest.spyOn( api, 'postWithEditTokenAndAssertUser' );
		const toBeWrittenEntity = {
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
		};
		const baseRevision = {
			revisionId: 123,
			entity: {} as Entity,
		};
		const expectedParams = {
			action: 'wbeditentity',
			baserevid: baseRevision.revisionId,
			id: toBeWrittenEntity.id,
			data: JSON.stringify( {
				claims: toBeWrittenEntity.statements,
			} ),
		};
		const assertUser = false;

		const entityWriter = new ApiWritingRepository( api );

		return entityWriter.saveEntity( toBeWrittenEntity, baseRevision, assertUser )
			.then( () => {
				expect( api.postWithEditToken ).toHaveBeenCalledTimes( 1 );
				expect( api.postWithEditToken ).toHaveBeenCalledWith( expectedParams );
			} );
	} );

	describe( 'if there is a problem', () => {
		it( 'bubbles non ApiErrors errors coming from the ApiCore', () => {
			const error = new Error( 'network timed out' );
			const mockWritingApi1 = mockApi( null, error );
			const toBeWrittenEntity = {
				id: 'Q123',
				labels: { de: { language: 'de', value: 'test' } },
				statements: {},
			};
			const entityWriter = new ApiWritingRepository( mockWritingApi1 );
			return expect( entityWriter.saveEntity( toBeWrittenEntity ) )
				.rejects
				.toBe( error );
		} );

		it( 'repackages ApiErrors into instances of ApplicationError wrapped into SavingError', () => {
			const assertAnonFailedError = { code: 'assertanonfailed' };
			const assertUserFailedError = { code: 'assertuserfailed' };
			const assertNamedUserFailedError = { code: 'assertnameduserfailed' };
			const editConflictError = { code: 'editconflict' };
			const badTagsError = { code: 'badtags' };
			const noSuchRevIdError = { code: 'nosuchrevid' };
			const someOtherApiError = { code: 'foo' };
			const apiErrors = new ApiErrors( [
				assertAnonFailedError,
				assertUserFailedError,
				assertNamedUserFailedError,
				editConflictError,
				badTagsError,
				noSuchRevIdError,
				someOtherApiError,
			] );
			const mockWritingApi1 = mockApi( null, apiErrors );
			const toBeWrittenEntity = {
				id: 'Q123',
				labels: { de: { language: 'de', value: 'test' } },
				statements: {},
			};
			const entityWriter = new ApiWritingRepository( mockWritingApi1 );

			const expectedError = new SavingError( [
				{ type: ErrorTypes.ASSERT_ANON_FAILED, info: assertAnonFailedError },
				{ type: ErrorTypes.ASSERT_USER_FAILED, info: assertUserFailedError },
				{ type: ErrorTypes.ASSERT_NAMED_USER_FAILED, info: assertNamedUserFailedError },
				{ type: ErrorTypes.EDIT_CONFLICT, info: editConflictError },
				{ type: ErrorTypes.BAD_TAGS, info: badTagsError },
				{ type: ErrorTypes.NO_SUCH_REVID, info: noSuchRevIdError },
				{ type: ErrorTypes.SAVING_FAILED, info: someOtherApiError },
			] );

			return expect( entityWriter.saveEntity( toBeWrittenEntity ) )
				.rejects
				.toStrictEqual( expectedError );
		} );
	} );
} );
