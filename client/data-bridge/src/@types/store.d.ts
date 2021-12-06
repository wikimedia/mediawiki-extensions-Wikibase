import ServiceContainer from '@/services/ServiceContainer';
// eslint-disable-next-line @typescript-eslint/no-unused-vars
import Store from 'vuex';

declare module 'vuex/types/index' {
	// eslint-disable-next-line @typescript-eslint/no-unused-vars
	interface Store<S> {
		$services: ServiceContainer;
	}
}
