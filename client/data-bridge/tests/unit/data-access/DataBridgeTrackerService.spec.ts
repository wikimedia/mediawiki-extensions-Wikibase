import DataBridgeTrackerService from '@/data-access/DataBridgeTrackerService';

describe( 'DataBridgeTrackerService', () => {
	it( 'tracks a given properyDataType', () => {
		const tracker = {
			increment: jest.fn(),
		};
		const service = new DataBridgeTrackerService( tracker );

		service.trackPropertyDatatype( 'string' );

		expect( tracker.increment ).toHaveBeenCalledWith(
			'counter.MediaWiki.wikibase.client.databridge.datatype.string',
		);
	} );
} );
