import MwInitTracker from '@/mediawiki/MwInitTracker';

describe( 'MwInitTracker', () => {

	it( 'tracks a given time to attach a link listener after mwStartupTime', () => {
		const tracker = {
			increment: jest.fn(),
			recordTiming: jest.fn(),
		};
		const mwStartupTime = 10;
		const timeLinkListenersAttached = 13;
		const performanceMock: any = {
			now: jest.fn().mockReturnValue( timeLinkListenersAttached ),
			getEntriesByName: jest.fn().mockReturnValue( [ { startTime: mwStartupTime } ] ),
		};
		const initTracker = new MwInitTracker( tracker, performanceMock, { hidden: false } );

		initTracker.recordTimeToLinkListenersAttached();

		expect( performanceMock.getEntriesByName ).toHaveBeenCalledWith( 'mwStartup' );
		expect( tracker.recordTiming ).toHaveBeenCalledWith(
			'timeToLinkListenersAttached',
			timeLinkListenersAttached - mwStartupTime,
		);
	} );

	it( 'does not track time to the link listener being attached if tab is in the background', () => {
		const tracker = {
			increment: jest.fn(),
			recordTiming: jest.fn(),
		};
		const mwStartupTime = 10;
		const timeLinkListenersAttached = 13;
		const performanceMock: any = {
			now: jest.fn().mockReturnValue( timeLinkListenersAttached ),
			getEntriesByName: jest.fn().mockReturnValue( [ { startTime: mwStartupTime } ] ),
		};
		const initTracker = new MwInitTracker( tracker, performanceMock, { hidden: true } );

		initTracker.recordTimeToLinkListenersAttached();

		expect( performanceMock.getEntriesByName ).not.toHaveBeenCalled();
		expect( tracker.recordTiming ).not.toHaveBeenCalled();
	} );

	it( 'is tracking the time it takes to open the modal after a click', () => {
		const tracker = {
			increment: jest.fn(),
			recordTiming: jest.fn(),
		};
		const performanceMock: any = {
			now: jest.fn(),
		};
		const timeAtClick = 10;
		const timeAtBridgeOpening = 15;
		performanceMock.now.mockReturnValueOnce( timeAtClick );
		performanceMock.now.mockReturnValueOnce( timeAtBridgeOpening );
		const initTracker = new MwInitTracker( tracker, performanceMock, { hidden: false } );

		const finishTracker = initTracker.startClickDelayTracker();
		expect( performanceMock.now ).toHaveBeenCalled();

		finishTracker();
		expect( performanceMock.now ).toHaveBeenCalledTimes( 2 );
		expect( tracker.recordTiming ).toHaveBeenCalledWith(
			'clickDelay',
			timeAtBridgeOpening - timeAtClick,
		);
	} );

} );
