import { MwTracker } from '@/@types/mediawiki/MwWindow';
import Tracker from '@/tracking/Tracker';

export default class EventTracker implements Tracker {
	private readonly tracker: MwTracker;

	public constructor( tracker: MwTracker ) {
		this.tracker = tracker;
	}

	public increment( topic: string ): void {
		this.tracker( `counter.${topic}`, 1 );
	}

	public recordTiming( topic: string, timeInMS: number ): void {
		this.tracker( `timing.${topic}`, timeInMS );
	}

}
