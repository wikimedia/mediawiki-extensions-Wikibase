import Vue, { VueConstructor } from 'vue';
import TrackingOptions from '@/@types/TrackingOptions';

export default function Track(
	vueConstructor: VueConstructor<Vue>,
	options: TrackingOptions,
): void {
	vueConstructor.prototype.$track = ( topic: string, data: string | number | object ) => {
		options.trackingFunction( topic, data );
	};
}
