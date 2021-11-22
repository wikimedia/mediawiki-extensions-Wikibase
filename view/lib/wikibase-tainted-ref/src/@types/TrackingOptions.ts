export type TrackFunction = ( topic: string, data?: object|number|string ) => void;

export default interface TrackingOptions {
	trackingFunction: TrackFunction;
}
