export default class BridgeConfig {
	public readonly usePublish: boolean;

	public constructor( usePublish: boolean ) {
		if ( typeof usePublish !== 'boolean' ) {
			throw new Error( 'No valid usePublish option provided.' );
		}

		this.usePublish = usePublish;
	}
}
