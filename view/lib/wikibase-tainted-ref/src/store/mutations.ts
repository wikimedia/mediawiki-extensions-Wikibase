import { MutationTree } from 'vuex';
import { SET_ALL_TAINTED } from '@/store/mutationTypes';
import Vue from 'vue';
import Application from '@/store/Application';

export const mutations: MutationTree<Application> = {
	[ SET_ALL_TAINTED ](
		state: Application,
		payload: string[],
	): void {
		payload.forEach( ( guid ) => {
			Vue.set( state.statementsTaintedState, guid, true );
		} );
	},
};
