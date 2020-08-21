import Tracker from '@/tracking/Tracker';

interface VisibilityApi extends Pick<Document, 'hidden'> {
	readonly msHidden?: boolean;
	readonly webkitHidden?: boolean;
}

export default class MwInitTracker {
	private readonly tracker: Tracker;
	private readonly performance: Performance;
	private readonly visibilityApi: VisibilityApi;

	public constructor( tracker: Tracker, performance: Performance, visibilityApi: VisibilityApi ) {
		this.tracker = tracker;
		this.performance = performance;
		this.visibilityApi = visibilityApi;
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
		if ( this.isTabInBackground() ) {
			// We only care of the performance of foreground tabs
			return;
		}
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

	private isTabInBackground(): boolean|undefined {
		return this.visibilityApi.hidden || this.visibilityApi.msHidden || this.visibilityApi.webkitHidden;
	}
}
