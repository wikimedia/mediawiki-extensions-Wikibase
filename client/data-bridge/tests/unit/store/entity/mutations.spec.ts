import { EntityState } from '@/store/entity/EntityState';
import EntityRevision from '@/datamodel/EntityRevision';
import newMockableEntityRevision from '../newMockableEntityRevision';
import newEntityState from './newEntityState';
import { inject } from 'vuex-smart-module';
import { EntityMutations } from '@/store/entity/mutations';

describe( 'entity/mutations', () => {
	describe( 'updateEntity', () => {
		it( 'contains entity data incl baseRevisionFingerprint after initialization', () => {
			const id = 'Q23';
			const state: EntityState = newEntityState();
			const entityRevision: EntityRevision = newMockableEntityRevision( { id, statements: {}, revisionId: 0 } );

			const mutations = inject( EntityMutations, { state } );

			mutations.updateEntity( entityRevision.entity );
			expect( state.id ).toBe( entityRevision.entity.id );
		} );
	} );

	it( 'updateRevision', () => {
		const state = newEntityState( { baseRevision: 0 } );
		const revision = 4711;

		const mutations = inject( EntityMutations, { state } );

		mutations.updateRevision( revision );
		expect( state.baseRevision ).toBe( revision );
	} );

	describe( 'reset', () => {
		it( 'removes all entity data', () => {
			const state = newEntityState( { id: 'Q1', baseRevision: 123 } );
			const mutations = inject( EntityMutations, { state } );

			mutations.reset();

			expect( state.id ).toBe( '' );
			expect( state.baseRevision ).toBe( 0 );
		} );
	} );
} );
