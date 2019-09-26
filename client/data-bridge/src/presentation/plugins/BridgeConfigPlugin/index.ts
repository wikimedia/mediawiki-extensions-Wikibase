import BridgeConfig from '@/presentation/plugins/BridgeConfigPlugin/BridgeConfig';
import Vue, { VueConstructor } from 'vue';

export interface BridgeConfigOptions {
	usePublish: boolean;
}

export default function BridgeConfigPlugin( vue: VueConstructor<Vue>, options?: BridgeConfigOptions ): void {
	if ( !options ) {
		throw new Error( 'No BridgeConfigOptions provided.' );
	}

	vue.prototype.$bridgeConfig = new BridgeConfig( options.usePublish );
}
