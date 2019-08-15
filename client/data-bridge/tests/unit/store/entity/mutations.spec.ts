import { mutations } from '@/store/entity/mutations';
import {
	ENTITY_UPDATE,
	ENTITY_REVISION_UPDATE,
} from '@/store/entity/mutationTypes';
import EntityState from '@/store/entity/EntityState';
import EntityRevision from '@/datamodel/EntityRevision';
import newMockableEntityRevision from '../newMockableEntityRevision';
import newEntityState from './newEntityState';

describe( 'entity/mutations', () => {
	describe( ENTITY_UPDATE, () => {
		it( 'contains entity data incl baseRevisionFingerprint after initialization', () => {
			const id = 'Q23';
			const state: EntityState = newEntityState();
			const entityRevision: EntityRevision = newMockableEntityRevision( { id, statements: {}, revisionId: 0 } );

			mutations[ ENTITY_UPDATE ]( state, entityRevision.entity );
			expect( state.id ).toBe( entityRevision.entity.id );
		} );
	} );

	it( ENTITY_REVISION_UPDATE, () => {
		const state = newEntityState( { baseRevision: 0 } );
		const revision = 4711;
		mutations[ ENTITY_REVISION_UPDATE ]( state, revision );
		expect( state.baseRevision ).toBe( revision );
	} );
} );
