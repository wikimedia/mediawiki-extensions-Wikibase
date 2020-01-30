import EntityRevision from '@/datamodel/EntityRevision';
import WritingEntityRepository from '@/definitions/data-access/WritingEntityRepository';
import {
	ENTITY_INIT,
	ENTITY_SAVE,
	ENTITY_WRITE,
} from '@/store/entity/actionTypes';
import {
	ENTITY_UPDATE,
	ENTITY_REVISION_UPDATE,
} from '@/store/entity/mutationTypes';
import {
	STATEMENTS_INIT,
} from '@/store/statements/actionTypes';
import newMockableEntityRevision from '../newMockableEntityRevision';
import { EntityActions } from '@/store/entity/actions';
import { inject } from 'vuex-smart-module';
import newMockServiceContainer from '../../services/newMockServiceContainer';
import newEntityState from './newEntityState';

describe( 'entity/actions', () => {
	const revidIncrementingWritingEntityRepository: WritingEntityRepository = {
		saveEntity: jest.fn( ( entity: EntityRevision ) => {
			return Promise.resolve(
				new EntityRevision( entity.entity, entity.revisionId + 1 ),
			);
		} ),
	};

	describe( ENTITY_INIT, () => {
		it( `dispatches to ${ENTITY_WRITE} on successful Entity lookup`, async () => {
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

			const dispatch = jest.fn();
			const actions = inject( EntityActions, {
				dispatch,
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					readingEntityRepository,
				} ),
			};

			await actions[ ENTITY_INIT ]( {
				entity: id,
				revision: revisionId,
			} );

			expect( dispatch ).toHaveBeenCalledWith( ENTITY_WRITE, entity );
		} );

		it( 'propagates error on failed lookup', async () => {
			const id = 'Q42';
			const revisionId = 4711;
			const error = new Error( 'this should be propagated' );

			const readingEntityRepository = {
				getEntity: jest.fn().mockRejectedValue( error ),
			};

			const dispatch = jest.fn();
			const actions = inject( EntityActions, {
				dispatch,
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					readingEntityRepository,
				} ),
			};

			await expect( actions[ ENTITY_INIT ]( {
				entity: id,
				revision: revisionId,
			} ) ).rejects.toBe( error );

			expect( dispatch ).not.toHaveBeenCalled();
		} );

	} );

	describe( ENTITY_SAVE, () => {
		it( 'updates entity and statements on successful save', async () => {
			const entityId = 'Q42',
				statements = {},
				entity = { id: entityId, statements },
				revision = 1234;

			const entityState = newEntityState( {
				baseRevision: revision,
				id: entityId,
			} );

			const resolvedValue = {};
			const dispatch = jest.fn().mockResolvedValue( resolvedValue );
			const actions = inject( EntityActions, {
				dispatch,
				state: entityState,
			} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					writingEntityRepository: revidIncrementingWritingEntityRepository,
				} ),
			};
			// @ts-ignore
			actions.statementsModule = {
				state: {
					[ entityId ]: statements,
				},
			};

			await expect( actions[ ENTITY_SAVE ]() ).resolves.toBe( resolvedValue );
			expect( revidIncrementingWritingEntityRepository.saveEntity ).toHaveBeenCalledWith( {
				entity,
				revisionId: revision,
			} );
			expect( dispatch ).toHaveBeenCalledWith( ENTITY_WRITE, {
				entity,
				revisionId: revision + 1,
			} );
		} );

		it( 'propagates error on failed save', async () => {
			const entityId = 'Q42',
				statements = {},
				error = new Error( 'this should be propagated' ),
				writingEntityRepository = {
					saveEntity( _entity: EntityRevision ): Promise<EntityRevision> {
						return Promise.reject( error );
					},
				},
				dispatch = jest.fn(),
				actions = inject( EntityActions, {
					dispatch,
					state: newEntityState(),
				} );

			// @ts-ignore
			actions.store = {
				$services: newMockServiceContainer( {
					writingEntityRepository,
				} ),
			};
			// @ts-ignore
			actions.statementsModule = {
				state: {
					[ entityId ]: statements,
				},
			};

			await expect( actions[ ENTITY_SAVE ]() ).rejects.toBe( error );
			expect( dispatch ).not.toHaveBeenCalled();
		} );
	} );

	describe( ENTITY_WRITE, () => {
		it(
			`commits to ${ENTITY_UPDATE} and ${ENTITY_REVISION_UPDATE} and dispatches ${STATEMENTS_INIT}`,
			async () => {
				const commit = jest.fn();
				const resolvedValue = {};
				const statementsDispatch = jest.fn().mockResolvedValue( resolvedValue );
				const actions = inject( EntityActions, {
					commit,
				} );
				// @ts-ignore
				actions.statementsModule = {
					dispatch: statementsDispatch,
				};

				const entityId = 'Q42';
				const revisionId = 4711;
				const statements = {};
				const entity = newMockableEntityRevision( { id: entityId, revisionId, statements } );

				await expect( actions[ ENTITY_WRITE ]( entity ) ).resolves.toBe( resolvedValue );

				expect( commit ).toHaveBeenCalledTimes( 2 );
				expect( commit ).toHaveBeenCalledWith( ENTITY_UPDATE, entity.entity );
				expect( commit ).toHaveBeenCalledWith( ENTITY_REVISION_UPDATE, revisionId );
				expect( statementsDispatch ).toHaveBeenCalledWith(
					STATEMENTS_INIT,
					{ entityId, statements },
				);
			},
		);

		it( 'rejects and propagates error if statements dispatch rejects', () => {
			const rejectedError = new Error();
			const statementsDispatch = jest.fn().mockRejectedValue( rejectedError );
			const actions = inject( EntityActions, { commit: jest.fn() } );
			// @ts-ignore
			actions.statementsModule = {
				dispatch: statementsDispatch,
			};
			const entity = newMockableEntityRevision();
			return expect( actions[ ENTITY_WRITE ]( entity ) ).rejects.toBe( rejectedError );
		} );
	} );
} );
