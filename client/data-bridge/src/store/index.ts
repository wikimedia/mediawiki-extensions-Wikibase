import Vue from 'vue';
import Vuex, { StoreOptions, Store } from 'vuex';

Vue.use( Vuex );
export function createStore(): Store<void> {
	return new Store<void>( {} as StoreOptions<void> );
}
