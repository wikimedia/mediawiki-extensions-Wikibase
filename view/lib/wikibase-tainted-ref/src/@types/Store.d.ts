import Application from '@/store/Application';
import { Store } from 'vuex';

declare module '@vue/runtime-core' {
	export interface ComponentCustomProperties {
		$store: Store<Application>;
	}
}
