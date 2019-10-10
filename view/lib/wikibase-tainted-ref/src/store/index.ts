import Vuex, { Store } from 'vuex';
import Application from './Application';
import Vue from 'vue';
import actions from '@/store/actions';
import { mutations } from '@/store/mutations';

Vue.use( Vuex );

export function createStore(): Store<Application> {
	const state: Application = {
		statementsTaintedState: { },
	};
	return new Store( {
		state,
		actions: actions(),
		mutations,
	} );
}
