import EventTracker from '@/mediawiki/facades/EventTracker';

describe( 'EventTracker', () => {
	it( 'tracks increment call', () => {
		const mwTrack = jest.fn();
		const service = new EventTracker( mwTrack );
		const topic = 'foo.bar.baz';

		service.increment( topic );

		expect( mwTrack ).toHaveBeenCalledWith( `counter.${topic}`, 1 );
	} );

	it( 'tracks timing call', () => {
		const mwTrack = jest.fn();
		const service = new EventTracker( mwTrack );
		const topic = 'foo.bar.baz';
		const timing = 123;

		service.recordTiming( topic, timing );

		expect( mwTrack ).toHaveBeenCalledWith( `timing.${topic}`, timing );
	} );
} );
