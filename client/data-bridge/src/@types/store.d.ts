import ServiceContainer from '@/services/ServiceContainer';
import Store from 'vuex';

declare module 'vuex/types/index' {

	interface Store<S> {
		$services: ServiceContainer;
	}
}
