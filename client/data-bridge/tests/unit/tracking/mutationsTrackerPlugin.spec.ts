import newMockTracker from '../../util/newMockTracker';
import mutationsTrackerPlugin from '@/tracking/mutationsTrackerPlugin';
import { ErrorTypes } from '@/definitions/ApplicationError';
import Application from '@/store/Application';
import { Store } from 'vuex';

describe( 'mutationsTrackerPlugin', () => {
	const trackError = jest.fn(),
		mockTracker = newMockTracker( { trackError } ),
		plugin = mutationsTrackerPlugin( mockTracker ),
		mockStore = { subscribe: jest.fn() } as Store<Application> & { subscribe: jest.Mock };

	it( 'subscribes to the mutations on the store', () => {
		expect( typeof plugin ).toBe( 'function' );

		plugin( mockStore );

		expect( mockStore.subscribe ).toHaveBeenCalledTimes( 1 );
		const callback = mockStore.subscribe.mock.calls[ 0 ][ 0 ];
		expect( typeof callback ).toBe( 'function' );
	} );

	it( 'tracks addApplicationErrors mutations', () => {
		plugin( mockStore );

		const callback = mockStore.subscribe.mock.calls[ 0 ][ 0 ];

		const errorType = ErrorTypes.APPLICATION_LOGIC_ERROR;
		callback( { type: 'addApplicationErrors', payload: [ { type: errorType } ] } );
		expect( trackError ).toHaveBeenCalledTimes( 1 );
		expect( trackError ).toHaveBeenCalledWith( errorType );
	} );

	it( 'does not track a random other mutation', () => {
		plugin( mockStore );

		const callback = mockStore.subscribe.mock.calls[ 0 ][ 0 ];

		callback( { type: 'setPageUrl', payload: [ 'Douglas_Adams' ] } );
		expect( trackError ).not.toHaveBeenCalled();
	} );
} );
