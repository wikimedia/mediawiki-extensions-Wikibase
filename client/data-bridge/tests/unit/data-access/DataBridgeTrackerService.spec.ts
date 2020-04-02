import DataBridgeTrackerService from '@/data-access/DataBridgeTrackerService';
import Tracker from '@/tracking/Tracker';

function getMockTracker(): Tracker {
	return {
		increment: jest.fn(),
		recordTiming: jest.fn(),
	};
}

describe( 'DataBridgeTrackerService', () => {
	it( 'tracks a given propertyDataType', () => {
		const tracker = getMockTracker();
		const service = new DataBridgeTrackerService( tracker );

		service.trackPropertyDatatype( 'string' );

		expect( tracker.increment ).toHaveBeenCalledWith( 'datatype.string' );
	} );

	it( 'tracks a title purge error', () => {
		const tracker = getMockTracker();
		const service = new DataBridgeTrackerService( tracker );

		service.trackTitlePurgeError();

		expect( tracker.increment ).toHaveBeenCalledWith( 'error.purge' );
	} );

	it( 'tracks a given unknown error type', () => {
		const tracker = getMockTracker();
		const service = new DataBridgeTrackerService( tracker );

		service.trackUnknownError( 'some_type' );

		expect( tracker.increment ).toHaveBeenCalledWith( 'error.unknown.some_type' );
	} );
} );
