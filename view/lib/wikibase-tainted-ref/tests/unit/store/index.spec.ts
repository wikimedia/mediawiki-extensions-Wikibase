import { TrackFunction } from '@/@types/TrackingOptions';
import { createStore } from '@/store';

describe( 'store/createStore ', () => {
	it( 'creates the store', () => {
		const mockTrackFunction: TrackFunction = jest.fn();
		const store = createStore( mockTrackFunction );
		expect( store ).toBeDefined();
		expect( store.state ).toBeDefined();
	} );
} );
