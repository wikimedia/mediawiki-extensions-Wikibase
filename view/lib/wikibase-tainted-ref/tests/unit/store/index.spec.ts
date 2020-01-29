import { createStore } from '@/store';
import { TrackFunction } from '@/store/TrackFunction';

describe( 'store/createStore ', () => {
	it( 'creates the store', () => {
		const mockTrackFunction: TrackFunction = jest.fn();
		const store = createStore( mockTrackFunction );
		expect( store ).toBeDefined();
		expect( store.state ).toBeDefined();
	} );
} );
