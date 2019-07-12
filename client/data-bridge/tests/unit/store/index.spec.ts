import { createStore } from '@/store';

describe( 'store/index', () => {
	it( 'creates the store', () => {
		const store = createStore();
		expect( store ).toBeDefined();
		expect( store.state ).toBeDefined();
		expect( store.state.targetProperty ).toBe( '' );
		expect( store.state.editFlow ).toBe( '' );
	} );
} );
