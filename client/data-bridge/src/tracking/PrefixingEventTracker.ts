import Tracker from './Tracker';

export default class PrefixingEventTracker implements Tracker {
	private readonly tracker: Tracker;
	private readonly trackingTopicPrefix: string;

	public constructor( tracker: Tracker, trackingTopicPrefix: string ) {
		this.tracker = tracker;
		this.trackingTopicPrefix = trackingTopicPrefix;
	}

	public increment( topic: string ): void {
		this.tracker.increment( `${this.trackingTopicPrefix}.${topic}` );
	}

	public recordTiming( topic: string, timeInMS: number ): void {
		this.tracker.recordTiming( `${this.trackingTopicPrefix}.${topic}`, timeInMS );
	}

}
