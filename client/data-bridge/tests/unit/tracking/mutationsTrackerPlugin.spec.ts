import newMockTracker from '../../util/newMockTracker';
import mutationsTrackerPlugin from '@/tracking/mutationsTrackerPlugin';
import { ErrorTypes } from '@/definitions/ApplicationError';

describe( 'mutationsTrackerPlugin', () => {
	it( 'tracks addApplicationErrors mutations', () => {
		const trackError = jest.fn();
		const mockTracker = newMockTracker( { trackError } );

		const plugin = mutationsTrackerPlugin( mockTracker );

		expect( typeof plugin === 'function' ).toBe( true );

		const mockStore = { subscribe: jest.fn() };
		// eslint-disable-next-line @typescript-eslint/ban-ts-ignore
		// @ts-ignore
		plugin( mockStore );

		expect( mockStore.subscribe ).toHaveBeenCalled();
		const callback = mockStore.subscribe.mock.calls[ 0 ][ 0 ];
		expect( typeof callback === 'function' ).toBe( true );

		const errorType = ErrorTypes.APPLICATION_LOGIC_ERROR;
		callback( { type: 'addApplicationErrors', payload: [ { type: errorType } ] } );
		expect( trackError ).toHaveBeenCalledWith( errorType );
	} );
} );
