import Tracker from '@/definitions/Tracker';
import BridgeTracker from '@/definitions/data-access/BridgeTracker';

export default class DataBridgeTrackerService implements BridgeTracker {
	private readonly BRIDGE_TOPIC_PREFIX = 'MediaWiki.wikibase.client.databridge';
	private readonly tracker: Tracker;

	public constructor( tracker: Tracker ) {
		this.tracker = tracker;
	}

	public trackPropertyDatatype( datatype: string ): void {
		this.tracker.increment( `${this.BRIDGE_TOPIC_PREFIX}.datatype.${datatype}` );
	}

	public trackTitlePurgeError(): void {
		this.tracker.increment( `${this.BRIDGE_TOPIC_PREFIX}.error.purge` );
	}
}
