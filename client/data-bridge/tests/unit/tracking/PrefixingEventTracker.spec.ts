import Tracker from '@/tracking/Tracker';
import PrefixingEventTracker from '@/tracking/PrefixingEventTracker';

function newMockTracker(): Tracker {
	return {
		increment: jest.fn(),
		recordTiming: jest.fn(),
	};
}

describe( 'PrefixingEventTracker', () => {
	it( 'prefixes increment calls', () => {
		const proxied = newMockTracker();
		const prefixingEventTracker = new PrefixingEventTracker( proxied, 'foo.bar' );

		prefixingEventTracker.increment( 'baz' );
		expect( proxied.increment ).toHaveBeenCalledWith( 'foo.bar.baz' );
	} );

	it( 'prefixes recordTiming calls', () => {
		const proxied = newMockTracker();
		const timeInMs = 507;
		const prefixingEventTracker = new PrefixingEventTracker( proxied, 'foo.bar' );

		prefixingEventTracker.recordTiming( 'baz', timeInMs );
		expect( proxied.recordTiming ).toHaveBeenCalledWith( 'foo.bar.baz', timeInMs );
	} );
} );
