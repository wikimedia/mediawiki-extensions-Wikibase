import Vue from 'vue';
import Vuex, { StoreOptions, Store } from 'vuex';
import Application from '@/store/Application';
import { mutations } from '@/store/mutations';

Vue.use( Vuex );

export function createStore(): Store<Application> {
	const state: Application = {
		targetProperty: '',
		editFlow: '',
	};

	const storeBundle: StoreOptions<Application> = {
		state,
		mutations,
		strict: process.env.NODE_ENV !== 'production',
	};

	return new Store<Application>( storeBundle );
}
