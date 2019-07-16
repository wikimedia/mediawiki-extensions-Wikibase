import { actions } from '@/store/entity/actions';
import {
	ENTITY_INIT,
} from '@/store/entity/actionTypes';
import {
	ENTITY_UPDATE,
	ENTITY_REVISION_UPDATE,
} from '@/store/entity/mutationTypes';
import { services } from '@/services';
import newMockStore from '../newMockStore';
import newMockableEntityRevision from '../newMockableEntityRevision';

describe( 'entity/actions', () => {
	describe( ENTITY_INIT, () => {
		beforeEach( () => {
			services.setEntityRepository( {
				getEntity: () => Promise.resolve(
					newMockableEntityRevision( {
						id: 'Q123',
						statements: {},
						revisionId: 42,
					} ),
				),
			} );
		} );

		it( `commits to ${ENTITY_UPDATE} on successful Entity lookup`, ( done ) => {
			const id = 'Q42';
			const revisionId = 4711;
			const entity = newMockableEntityRevision( { id, revisionId } );

			services.setEntityRepository( {
				getEntity: ( thisEntityId: string, thisRevision: number ) => {
					expect( thisEntityId ).toBe( id );
					expect( thisRevision ).toBe( revisionId );
					return Promise.resolve( entity );
				},
			} );

			const context = newMockStore( {
				commit: jest.fn(),
			} );

			actions[ ENTITY_INIT ]( context, { entity: id, revision: revisionId } ).then( () => {
				expect( context.commit ).toBeCalledWith(
					ENTITY_UPDATE,
					entity.entity,
				);
				done();
			} );
		} );

		it( `commits to ${ENTITY_REVISION_UPDATE} on successful entity lookup`, () => {
			const revisionId = 4711;
			const entity = newMockableEntityRevision( { revisionId } );
			services.setEntityRepository( {
				getEntity: () => {
					return Promise.resolve( entity );
				},
			} );

			const context = newMockStore( {
				commit: jest.fn(),
			} );

			return actions[ ENTITY_INIT ]( context, { entity: 'Q123', revision: revisionId } ).then( () => {
				expect( context.commit ).toHaveBeenCalledWith( ENTITY_REVISION_UPDATE, revisionId );
			} );
		} );
	} );
} );
