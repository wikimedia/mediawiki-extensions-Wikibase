/**
 * Maybe one day we can replace this by an industry-standard interface
 */
export default interface Tracker {
	increment( topic: string ): void;
	recordTiming( topic: string, timeInMS: number ): void;
}
