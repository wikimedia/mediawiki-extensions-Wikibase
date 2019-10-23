import { MwTracker } from '@/@types/mediawiki/MwWindow';
import Tracker from '@/definitions/Tracker';

export default class EventTracker implements Tracker {
	private readonly tracker: MwTracker;

	public constructor( tracker: MwTracker ) {
		this.tracker = tracker;
	}

	public increment( topic: string ): void {
		this.tracker( topic, 1 );
	}

}
