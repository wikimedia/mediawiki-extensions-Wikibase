import DataBridgeTrackerService from '@/data-access/DataBridgeTrackerService';

describe( 'DataBridgeTrackerService', () => {
	it( 'tracks a given propertyDataType', () => {
		const tracker = {
			increment: jest.fn(),
			recordTiming: jest.fn(),
		};
		const service = new DataBridgeTrackerService( tracker );

		service.trackPropertyDatatype( 'string' );

		expect( tracker.increment ).toHaveBeenCalledWith(
			'MediaWiki.wikibase.client.databridge.datatype.string',
		);
	} );
} );
