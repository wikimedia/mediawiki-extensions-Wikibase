import Vue from 'vue';
import Vuex, { StoreOptions, Store } from 'vuex';

Vue.use( Vuex );
export function createStore(): Store<void> {
	return new Vuex.Store<void>( {} as StoreOptions<void> );
}
