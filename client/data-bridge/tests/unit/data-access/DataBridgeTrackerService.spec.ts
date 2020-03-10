import DataBridgeTrackerService from '@/data-access/DataBridgeTrackerService';
import Tracker from '@/definitions/Tracker';

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

		expect( tracker.increment ).toHaveBeenCalledWith(
			'MediaWiki.wikibase.client.databridge.datatype.string',
		);
	} );

	it( 'tracks a title purge error', () => {
		const tracker = getMockTracker();
		const service = new DataBridgeTrackerService( tracker );

		service.trackTitlePurgeError();

		expect( tracker.increment ).toHaveBeenCalledWith(
			'MediaWiki.wikibase.client.databridge.error.purge',
		);
	} );
} );
