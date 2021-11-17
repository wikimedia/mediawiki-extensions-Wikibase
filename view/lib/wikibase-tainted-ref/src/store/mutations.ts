import { MutationTree } from 'vuex';
import {
	SET_ALL_UNTAINTED,
	SET_ALL_POPPERS_HIDDEN,
	SET_POPPER_HIDDEN,
	SET_POPPER_VISIBLE,
	SET_TAINTED,
	SET_UNTAINTED,
	SET_HELP_LINK,
	SET_ALL_EDIT_MODE_FALSE,
	SET_STATEMENT_EDIT_FALSE,
	SET_STATEMENT_EDIT_TRUE,
} from '@/store/mutationTypes';
import Application from '@/store/Application';

export const mutations: MutationTree<Application> = {
	[ SET_ALL_UNTAINTED ](
		state: Application,
		guids: string[],
	): void {
		guids.forEach( ( guid ) => {
			state.statementsTaintedState[ guid ] = false;
		} );
	},
	[ SET_ALL_POPPERS_HIDDEN ](
		state: Application,
		guids: string[],
	): void {
		guids.forEach( ( guid ) => {
			state.statementsPopperIsOpen[ guid ] = false;
		} );
	},
	[ SET_ALL_EDIT_MODE_FALSE ](
		state: Application,
		guids: string[],
	): void {
		guids.forEach( ( guid ) => {
			state.statementsEditState[ guid ] = false;
		} );
	},
	[ SET_TAINTED ](
		state: Application,
		guid: string,
	): void {
		state.statementsTaintedState[ guid ] = true;
	},
	[ SET_UNTAINTED ](
		state: Application,
		guid: string,
	): void {
		state.statementsTaintedState[ guid ] = false;
	},
	[ SET_STATEMENT_EDIT_TRUE ](
		state: Application,
		guid: string,
	): void {
		state.statementsEditState[ guid ] = true;
	},
	[ SET_STATEMENT_EDIT_FALSE ](
		state: Application,
		guid: string,
	): void {
		state.statementsEditState[ guid ] = false;
	},
	[ SET_POPPER_HIDDEN ](
		state: Application,
		guid: string,
	): void {
		state.statementsPopperIsOpen[ guid ] = false;
	},
	[ SET_POPPER_VISIBLE ](
		state: Application,
		guid: string,
	): void {
		state.statementsPopperIsOpen[ guid ] = true;
	},
	[ SET_HELP_LINK ](
		state: Application,
		url: string,
	): void {
		state.helpLink = url;
	},
};
