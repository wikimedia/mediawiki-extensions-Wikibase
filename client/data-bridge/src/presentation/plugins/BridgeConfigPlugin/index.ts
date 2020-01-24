import BridgeConfig, { BridgeConfigOptions } from '@/presentation/plugins/BridgeConfigPlugin/BridgeConfig';
import Vue, { VueConstructor } from 'vue';

export default function BridgeConfigPlugin( vue: VueConstructor<Vue>, options?: BridgeConfigOptions ): void {
	if ( !options ) {
		throw new Error( 'No BridgeConfigOptions provided.' );
	}

	vue.prototype.$bridgeConfig = new BridgeConfig( options );
}
