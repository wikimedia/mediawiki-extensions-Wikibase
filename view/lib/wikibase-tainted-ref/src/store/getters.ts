import { GetterTree } from 'vuex';
import Application from '@/store/Application';

export const getters: GetterTree<Application, Application> = {
	statementsTaintedState( state: Application ): Function {
		return ( guid: string ): boolean => {
			return state.statementsTaintedState[ guid ];
		};
	},
	popperState( state: Application ): Function {
		return ( guid: string ): boolean => {
			return state.statementsPopperIsOpen[ guid ];
		};
	},
	helpLink( state: Application ): string {
		return state.helpLink;
	},
	feedbackLink( state: Application ): string {
		return state.feedbackLink;
	},
};
