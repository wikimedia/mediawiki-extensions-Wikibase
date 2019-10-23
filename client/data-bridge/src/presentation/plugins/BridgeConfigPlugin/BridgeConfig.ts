import WikibaseClientConfiguration from '@/definitions/WikibaseClientConfiguration';
import Configuration from '@/definitions/Configuration';

export type BridgeConfigOptions = WikibaseClientConfiguration | Configuration;

export default class BridgeConfig {
	public readonly usePublish: boolean;
	public readonly stringMaxLength: number|null;

	public constructor( config: BridgeConfigOptions ) {
		if ( typeof config.usePublish !== 'boolean' ) {
			throw new Error( 'No valid usePublish option provided.' );
		}

		this.usePublish = config.usePublish;

		if ( ( config as Configuration ).dataTypeLimits ) {
			if ( typeof ( config as Configuration ).dataTypeLimits.string.maxLength !== 'number' ) {
				throw new Error( 'No valid stringMaxLength option provided.' );
			}

			this.stringMaxLength = ( config as Configuration ).dataTypeLimits.string.maxLength;
		} else {
			this.stringMaxLength = null;
		}
	}
}
