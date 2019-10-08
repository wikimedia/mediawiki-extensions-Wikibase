import { createStore } from '@/store';

describe( 'store/createStore ', () => {
	it( 'creates the store', () => {
		const store = createStore();
		expect( store ).toBeDefined();
		expect( store.state ).toBeDefined();
	} );
} );
