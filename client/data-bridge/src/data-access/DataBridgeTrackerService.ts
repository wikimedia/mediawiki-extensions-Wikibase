import Tracker from '@/tracking/Tracker';
import BridgeTracker from '@/definitions/data-access/BridgeTracker';
import errorTypeFormatter from '@/utils/errorTypeFormatter';

export default class DataBridgeTrackerService implements BridgeTracker {
	private readonly tracker: Tracker;

	public constructor( tracker: Tracker ) {
		this.tracker = tracker;
	}

	public trackPropertyDatatype( datatype: string ): void {
		this.tracker.increment( `datatype.${datatype}` );
	}

	public trackError( type: string ): void {
		this.tracker.increment( `error.all.${errorTypeFormatter( type )}` );
	}

	public trackRecoveredError( type: string ): void {
		this.tracker.increment( `error.recovered.${errorTypeFormatter( type )}` );
	}

	public trackUnknownError( type: string ): void {
		this.tracker.increment( `error.unknown.${errorTypeFormatter( type )}` );
	}

	public trackSavingUnknownError( type: string ): void {
		this.tracker.increment( `error.onsave.unknown.${errorTypeFormatter( type )}` );
	}
}
