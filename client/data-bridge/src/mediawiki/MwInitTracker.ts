import Tracker from '@/tracking/Tracker';

export default class MwInitTracker {
	private readonly tracker: Tracker;
	private readonly performance: Performance;

	public constructor( tracker: Tracker, performance: Performance ) {
		this.tracker = tracker;
		this.performance = performance;
	}

	/**
	 * @returns method to finish the tracking
	 */
	public startClickDelayTracker(): () => void {
		const clickStarted = this.performance.now();

		return (): void => {
			const clickDelay = this.performance.now() - clickStarted;
			this.tracker.recordTiming( 'clickDelay', clickDelay );
		};
	}

	public recordTimeToLinkListenersAttached(): void {
		const now = this.performance.now();
		if ( !this.performance.getEntriesByName || this.performance.getEntriesByName( 'mwStartup' ).length === 0 ) {
			// not browser or not mediawiki environment
			return;
		}
		const mwStartupMark = this.performance.getEntriesByName( 'mwStartup' )[ 0 ];
		this.tracker.recordTiming(
			'timeToLinkListenersAttached',
			now - mwStartupMark.startTime,
		);
	}
}
