import { MutationTree } from 'vuex';
import { SET_ALL_UNTAINTED, SET_TAINTED, SET_UNTAINTED } from '@/store/mutationTypes';
import Vue from 'vue';
import Application from '@/store/Application';

export const mutations: MutationTree<Application> = {
	[ SET_ALL_UNTAINTED ](
		state: Application,
		payload: string[],
	): void {
		payload.forEach( ( guid ) => {
			Vue.set( state.statementsTaintedState, guid, false );
		} );
	},
	[ SET_TAINTED ](
		state: Application,
		payload: string,
	): void {
		state.statementsTaintedState[ payload ] = true;
	},
	[ SET_UNTAINTED ](
		state: Application,
		payload: string,
	): void {
		state.statementsTaintedState[ payload ] = false;
	},
};
