interface TrackingOptions {
	trackingFunction( topic: string, data: object|number|string ): void;
}

export default TrackingOptions;
