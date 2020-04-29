import { GetterTree } from 'vuex';
import Application from '@/store/Application';
import {
	GET_EDIT_STATE,
	GET_HELP_LINK,
	GET_POPPER_STATE,
	GET_STATEMENT_TAINTED_STATE,
} from '@/store/getterTypes';

export const getters: GetterTree<Application, Application> = {
	[ GET_STATEMENT_TAINTED_STATE ]( state: Application ): Function {
		return ( guid: string ): boolean => {
			return state.statementsTaintedState[ guid ];
		};
	},
	[ GET_POPPER_STATE ]( state: Application ): Function {
		return ( guid: string ): boolean => {
			return state.statementsPopperIsOpen[ guid ];
		};
	},
	[ GET_EDIT_STATE ]( state: Application ): Function {
		return ( guid: string ): boolean => {
			return state.statementsEditState[ guid ];
		};
	},
	[ GET_HELP_LINK ]( state: Application ): string {
		return state.helpLink;
	},
};
