import { mutations } from '@/store/entity/mutations';
import {
	ENTITY_UPDATE,
	ENTITY_REVISION_UPDATE,
} from '@/store/entity/mutationTypes';
import EntityState from '@/store/entity/EntityState';
import EntityRevision from '@/datamodel/EntityRevision';
import newMockableEntityRevision from '../newMockableEntityRevision';
import newEntityState from './newEntityState';
import StatementMap from '@/datamodel/StatementMap';

describe( 'entity/mutations', () => {
	describe( ENTITY_UPDATE, () => {
		it( 'contains entity data incl baseRevisionFingerprint after initialization', () => {
			const id = 'Q23';
			const state: EntityState = newEntityState();
			const entityRevision: EntityRevision = newMockableEntityRevision( { id, statements: {}, revisionId: 0 } );

			mutations[ ENTITY_UPDATE ]( state, entityRevision.entity );
			expect( state.id ).toBe( entityRevision.entity.id );
		} );

		it( 'contains entity data statements after initialization', () => {
			const statements: StatementMap = { P31: [] },
				state = newEntityState(),
				entityRevision = newMockableEntityRevision( { statements } );

			mutations[ ENTITY_UPDATE ]( state, entityRevision.entity );
			expect( state.statements ).toBe( statements );
		} );
	} );

	it( ENTITY_REVISION_UPDATE, () => {
		const state = newEntityState( { baseRevision: 0 } );
		const revision = 4711;
		mutations[ ENTITY_REVISION_UPDATE ]( state, revision );
		expect( state.baseRevision ).toBe( revision );
	} );
} );
