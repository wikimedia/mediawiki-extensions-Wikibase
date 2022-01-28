import TrackingOptions from '@/@types/TrackingOptions';
import { App } from 'vue';

export default function Track(
	app: App,
	options: TrackingOptions,
): void {
	app.config.globalProperties.$track = ( topic: string, data: string | number | object ) => {
		options.trackingFunction( topic, data );
	};
}
