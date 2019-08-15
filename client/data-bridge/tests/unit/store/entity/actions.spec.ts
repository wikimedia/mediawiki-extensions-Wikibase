import actions from '@/store/entity/actions';
import {
	ENTITY_INIT,
} from '@/store/entity/actionTypes';
import {
	ENTITY_UPDATE,
	ENTITY_REVISION_UPDATE,
} from '@/store/entity/mutationTypes';
import {
	NS_STATEMENTS,
} from '@/store/namespaces';
import {
	STATEMENTS_INIT,
} from '@/store/entity/statements/actionTypes';
import namespacedStoreEvent from '@/store/namespacedStoreEvent';
import newMockStore from '../newMockStore';
import newMockableEntityRevision from '../newMockableEntityRevision';

describe( 'entity/actions', () => {
	describe( ENTITY_INIT, () => {

		it( `commits to ${ENTITY_UPDATE} on successful Entity lookup`, () => {
			const id = 'Q42';
			const revisionId = 4711;
			const entity = newMockableEntityRevision( { id, revisionId } );

			const entityRepository = {
				getEntity: ( thisEntityId: string, thisRevision: number ) => {
					expect( thisEntityId ).toBe( id );
					expect( thisRevision ).toBe( revisionId );
					return Promise.resolve( entity );
				},
			};

			const context = newMockStore( {
				commit: jest.fn(),
			} );

			return ( actions( entityRepository )[ ENTITY_INIT ] as Function )( context, {
				entity: id,
				revision: revisionId,
			} ).then( () => {
				expect( context.commit ).toBeCalledWith(
					ENTITY_UPDATE,
					entity.entity,
				);
			} );
		} );

		it( `commits to ${ENTITY_REVISION_UPDATE} on successful entity lookup`, () => {
			const revisionId = 4711;
			const entity = newMockableEntityRevision( { revisionId } );
			const entityRepository = {
				getEntity: () => Promise.resolve( entity ),
			};

			const context = newMockStore( {
				commit: jest.fn(),
			} );

			return ( actions( entityRepository )[ ENTITY_INIT ] as Function )( context, {
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
				const entityRepository = {
					getEntity: () => Promise.resolve( entity ),
				};

				return ( actions( entityRepository )[ ENTITY_INIT ] as Function )( context, {
					entity: id,
					revision: revisionId,
				} ).then( () => {
					expect( context.dispatch ).toBeCalledWith(
						namespacedStoreEvent( NS_STATEMENTS, STATEMENTS_INIT ),
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
				const entityRepository = {
					getEntity: () => Promise.resolve( entity ),
				};

				return ( actions( entityRepository ) as any )[ ENTITY_INIT ](
					context,
					{
						entity: id,
						revision: revisionId,
					},
				).catch( ( error: Error ) => {
					expect( error.message ).toBe( errorMsg );
				} );
			} );
		} );
	} );
} );
