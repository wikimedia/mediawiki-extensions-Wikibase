import { createStore } from '@/store';

describe( 'store/index', () => {
	it( 'creates the store', () => {
		const store = createStore();
		expect( store ).toBeDefined();
	} );
} );
