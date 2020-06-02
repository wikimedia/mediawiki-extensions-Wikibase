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

	it( 'tracks a given error type', () => {
		const tracker = getMockTracker();
		const service = new DataBridgeTrackerService( tracker );

		service.trackError( 'Error with Strange formatting!' );

		expect( tracker.increment ).toHaveBeenCalledWith( 'error.all.error_with_strange_formatting' );
	} );

	it( 'tracks a given unknown error type', () => {
		const tracker = getMockTracker();
		const service = new DataBridgeTrackerService( tracker );

		service.trackUnknownError( 'Error with Strange formatting!' );

		expect( tracker.increment ).toHaveBeenCalledWith( 'error.unknown.error_with_strange_formatting' );
	} );

	it( 'tracks a given unknown saving error type', () => {
		const tracker = getMockTracker();
		const service = new DataBridgeTrackerService( tracker );

		service.trackSavingUnknownError( 'Error with Strange formatting!' );

		expect( tracker.increment ).toHaveBeenCalledWith( 'error.onsave.unknown.error_with_strange_formatting' );
	} );
} );
