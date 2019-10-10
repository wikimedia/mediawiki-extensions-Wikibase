import { STATEMENT_TAINTED_STATE_INIT, STATEMENT_TAINTED_STATE_UNTAINT } from '@/store/actionTypes';
import Application from './Application';
import { ActionContext, ActionTree } from 'vuex';
import { SET_ALL_TAINTED, SET_UNTAINTED } from '@/store/mutationTypes';

export default function actions(): ActionTree<Application, Application> {
	return {
		[ STATEMENT_TAINTED_STATE_INIT ](
			context: ActionContext<Application, Application>,
			payload: string[],
		): void {
			context.commit( SET_ALL_TAINTED, payload );
		},
		[ STATEMENT_TAINTED_STATE_UNTAINT ](
			context: ActionContext<Application, Application>,
			payload: string,
		): void {
			context.commit( SET_UNTAINTED, payload );
		},
	};
}
