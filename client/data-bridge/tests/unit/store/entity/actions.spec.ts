import EntityRevision from '@/datamodel/EntityRevision';
import ReadingEntityRepository from '@/definitions/data-access/ReadingEntityRepository';
import WritingEntityRepository from '@/definitions/data-access/WritingEntityRepository';
import actions from '@/store/entity/actions';
import {
	ENTITY_INIT,
	ENTITY_SAVE,
} from '@/store/entity/actionTypes';
import {
	ENTITY_ID,
	ENTITY_REVISION,
} from '@/store/entity/getterTypes';
import {
	ENTITY_UPDATE,
	ENTITY_REVISION_UPDATE,
} from '@/store/entity/mutationTypes';
import { STATEMENTS_MAP } from '@/store/entity/statements/getterTypes';
import {
	NS_STATEMENTS,
} from '@/store/namespaces';
import {
	STATEMENTS_INIT,
} from '@/store/entity/statements/actionTypes';
import { action, getter } from '@wmde/vuex-helpers/dist/namespacedStoreMethods';
import newMockStore from '../newMockStore';
import newMockableEntityRevision from '../newMockableEntityRevision';

describe( 'entity/actions', () => {
	describe( ENTITY_INIT, () => {

		const neverReadingEntityRepository: ReadingEntityRepository = {
			getEntity( _id: string, _revision: number ): Promise<EntityRevision> {
				throw new Error( 'should not use ReadingEntityRepository' );
			},
		};

		const neverWritingEntityRepository: WritingEntityRepository = {
			saveEntity( _entity: EntityRevision ): Promise<EntityRevision> {
				// note: this deliberately throws an error
				// instead of returning a rejected promise
				throw new Error( 'should not use WritingEntityRepository' );
			},
		};

		const revidIncrementingWritingEntityRepository: WritingEntityRepository = {
			saveEntity( entity: EntityRevision ): Promise<EntityRevision> {
				return Promise.resolve(
					new EntityRevision( entity.entity, entity.revisionId + 1 ),
				);
			},
		};

		it( `commits to ${ENTITY_UPDATE} on successful Entity lookup`, () => {
			const id = 'Q42';
			const revisionId = 4711;
			const entity = newMockableEntityRevision( { id, revisionId } );

			const readingEntityRepository = {
				getEntity: ( thisEntityId: string, thisRevision: number ) => {
					expect( thisEntityId ).toBe( id );
					expect( thisRevision ).toBe( revisionId );
					return Promise.resolve( entity );
				},
			};

			const context = newMockStore( {
				commit: jest.fn(),
			} );

			const action = actions( readingEntityRepository, neverWritingEntityRepository )[ ENTITY_INIT ];
			return ( action as Function )( context, {
				entity: id,
				revision: revisionId,
			} ).then( () => {
				expect( context.commit ).toHaveBeenCalledWith(
					ENTITY_UPDATE,
					entity.entity,
				);
			} );
		} );

		it( `commits to ${ENTITY_REVISION_UPDATE} on successful entity lookup`, () => {
			const revisionId = 4711;
			const entity = newMockableEntityRevision( { revisionId } );
			const readingEntityRepository = {
				getEntity: () => Promise.resolve( entity ),
			};

			const context = newMockStore( {
				commit: jest.fn(),
			} );

			const action = actions( readingEntityRepository, neverWritingEntityRepository )[ ENTITY_INIT ];
			return ( action as Function )( context, {
				entity: 'Q123',
				revision: revisionId,
			} ).then( () => {
				expect( context.commit ).toHaveBeenCalledWith( ENTITY_REVISION_UPDATE, revisionId );
			} );
		} );

		describe( 'dispatch to statements', () => {
			it( 'dispatches to statement module', () => {
				const id = 'Q42';
				const revisionId = 4711;
				const statements = { Q42: {} as any };
				const dispatch = jest.fn();
				const context = newMockStore( { dispatch } );
				const entity = newMockableEntityRevision( { id, revisionId, statements } );
				const readingEntityRepository = {
					getEntity: () => Promise.resolve( entity ),
				};

				const entityInit = actions( readingEntityRepository, neverWritingEntityRepository )[ ENTITY_INIT ];
				return ( entityInit as Function )( context, {
					entity: id,
					revision: revisionId,
				} ).then( () => {
					expect( context.dispatch ).toHaveBeenCalledWith(
						action( NS_STATEMENTS, STATEMENTS_INIT ),
						{
							entityId: id,
							statements,
						},
					);
				} );
			} );

			it( 'propagates errors', () => {
				const id = 'Q42';
				const revisionId = 4711;
				const statements = { Q42: {} as any };
				const errorMsg = 'sample error';
				const dispatch = jest.fn().mockImplementation( () => {
					return new Promise( () => {
						throw new Error( errorMsg );
					} );
				} );
				const context = newMockStore( { dispatch } );
				const entity = newMockableEntityRevision( { id, revisionId, statements } );
				const readingEntityRepository = {
					getEntity: () => Promise.resolve( entity ),
				};

				const entityInit = actions( readingEntityRepository, neverWritingEntityRepository )[ ENTITY_INIT ];
				return ( entityInit as Function )( context, {
					entity: id,
					revision: revisionId,
				} ).catch( ( error: Error ) => {
					expect( error.message ).toBe( errorMsg );
				} );
			} );
		} );

		it( 'updates entity and statements on successful save', () => {
			const entityId = 'Q42',
				statements = {},
				entity = { id: entityId, statements },
				revision = 1234,
				statementsGetter = jest.fn( () => statements ),
				context = newMockStore( {
					commit: jest.fn(),
					getters: {
						[ ENTITY_ID ]: entityId,
						[ ENTITY_REVISION ]: revision,
						[ getter( NS_STATEMENTS, STATEMENTS_MAP ) ]: statementsGetter,
					},
				} );

			const entitySaveAction = actions(
				neverReadingEntityRepository,
				revidIncrementingWritingEntityRepository,
			)[ ENTITY_SAVE ];
			return ( entitySaveAction as Function )( context )
				.then( () => {
					expect( context.commit ).toHaveBeenCalledWith( ENTITY_UPDATE, entity );
					expect( context.commit ).toHaveBeenCalledWith( ENTITY_REVISION_UPDATE, revision + 1 );
					expect( context.dispatch ).toHaveBeenCalledWith(
						action( NS_STATEMENTS, STATEMENTS_INIT ),
						{ entityId, statements },
					);
				} );
		} );

		it( 'propagates error on failed save', () => {
			const entityId = 'Q42',
				statements = {},
				revision = 1234,
				statementsGetter = jest.fn( () => statements ),
				context = newMockStore( {
					commit: jest.fn(),
					getters: {
						[ ENTITY_ID ]: entityId,
						[ ENTITY_REVISION ]: revision,
						[ getter( NS_STATEMENTS, STATEMENTS_MAP ) ]: statementsGetter,
					},
				} ),
				error = new Error( 'this should be propagated' ),
				writingEntityRepository = {
					saveEntity( _entity: EntityRevision ): Promise<EntityRevision> {
						return Promise.reject( error );
					},
				};

			const action = actions( neverReadingEntityRepository, writingEntityRepository )[ ENTITY_SAVE ];
			return expect( ( action as Function )( context ) )
				.rejects
				.toStrictEqual( error );
		} );
	} );
} );
