import { createStore } from '@/store';
import ServiceRepositories from '@/services/ServiceRepositories';

describe( 'store/index', () => {
	it( 'creates the store', () => {
		const store = createStore( {
			getReadingEntityRepository() {
				return {};
			},
			getWritingEntityRepository() {
				return {};
			},
			getEntityLabelRepository() {
				return {};
			},
		} as ServiceRepositories );
		expect( store ).toBeDefined();
		expect( store.state ).toBeDefined();
		expect( store.state.targetProperty ).toBe( '' );
		expect( store.state.editFlow ).toBe( '' );
	} );
} );
