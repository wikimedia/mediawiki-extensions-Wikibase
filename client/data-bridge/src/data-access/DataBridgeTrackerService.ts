import Tracker from '@/tracking/Tracker';
import BridgeTracker from '@/definitions/data-access/BridgeTracker';

export default class DataBridgeTrackerService implements BridgeTracker {
	private readonly tracker: Tracker;

	public constructor( tracker: Tracker ) {
		this.tracker = tracker;
	}

	public trackPropertyDatatype( datatype: string ): void {
		this.tracker.increment( `datatype.${datatype}` );
	}

	public trackTitlePurgeError(): void {
		this.tracker.increment( 'error.purge' );
	}
}
