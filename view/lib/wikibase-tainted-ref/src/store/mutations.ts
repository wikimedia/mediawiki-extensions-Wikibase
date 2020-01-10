import { MutationTree } from 'vuex';
import {
	SET_ALL_UNTAINTED,
	SET_ALL_POPPERS_HIDDEN,
	SET_POPPER_HIDDEN,
	SET_POPPER_VISIBLE,
	SET_TAINTED,
	SET_UNTAINTED,
	SET_HELP_LINK,
	SET_FEEDBACK_LINK,
	SET_ALL_EDIT_MODE_FALSE,
	SET_STATEMENT_EDIT_FALSE,
	SET_STATEMENT_EDIT_TRUE,
} from '@/store/mutationTypes';
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
	[ SET_ALL_POPPERS_HIDDEN ](
		state: Application,
		payload: string[],
	): void {
		payload.forEach( ( guid ) => {
			Vue.set( state.statementsPopperIsOpen, guid, false );
		} );
	},
	[ SET_ALL_EDIT_MODE_FALSE ](
		state: Application,
		payload: string[],
	): void {
		payload.forEach( ( guid ) => {
			Vue.set( state.statementsEditState, guid, false );
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
	[ SET_STATEMENT_EDIT_TRUE ](
		state: Application,
		payload: string,
	): void {
		state.statementsEditState[ payload ] = true;
	},
	[ SET_STATEMENT_EDIT_FALSE ](
		state: Application,
		payload: string,
	): void {
		state.statementsEditState[ payload ] = false;
	},
	[ SET_POPPER_HIDDEN ](
		state: Application,
		payload: string,
	): void {
		state.statementsPopperIsOpen[ payload ] = false;
	},
	[ SET_POPPER_VISIBLE ](
		state: Application,
		payload: string,
	): void {
		state.statementsPopperIsOpen[ payload ] = true;
	},
	[ SET_HELP_LINK ](
		state: Application,
		payload: string,
	): void {
		state.helpLink = payload;
	},
	[ SET_FEEDBACK_LINK ](
		state: Application,
		payload: string,
	): void {
		state.feedbackLink = payload;
	},
};
