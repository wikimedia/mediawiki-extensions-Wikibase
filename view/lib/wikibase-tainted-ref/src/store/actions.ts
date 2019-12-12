import {
	POPPER_HIDE, POPPER_SHOW,
	STORE_INIT,
	STATEMENT_TAINTED_STATE_TAINT,
	STATEMENT_TAINTED_STATE_UNTAINT,
	HELP_LINK_SET, FEEDBACK_LINK_SET,
} from '@/store/actionTypes';
import Application from './Application';
import { ActionContext, ActionTree } from 'vuex';
import {
	SET_ALL_UNTAINTED,
	SET_ALL_POPPERS_HIDDEN,
	SET_POPPER_HIDDEN,
	SET_POPPER_VISIBLE,
	SET_TAINTED,
	SET_UNTAINTED,
	SET_HELP_LINK,
	SET_FEEDBACK_LINK,
} from '@/store/mutationTypes';

export default function actions(): ActionTree<Application, Application> {
	return {
		[ STORE_INIT ](
			context: ActionContext<Application, Application>,
			payload: string[],
		): void {
			context.commit( SET_ALL_UNTAINTED, payload );
			context.commit( SET_ALL_POPPERS_HIDDEN, payload );
		},
		[ STATEMENT_TAINTED_STATE_UNTAINT ](
			context: ActionContext<Application, Application>,
			payload: string,
		): void {
			context.commit( SET_UNTAINTED, payload );
			context.commit( SET_POPPER_HIDDEN, payload );
		},
		[ STATEMENT_TAINTED_STATE_TAINT ](
			context: ActionContext<Application, Application>,
			payload: string,
		): void {
			context.commit( SET_TAINTED, payload );
		},
		[ POPPER_HIDE ](
			context: ActionContext<Application, Application>,
			payload: string,
		): void {
			context.commit( SET_POPPER_HIDDEN, payload );
		},
		[ POPPER_SHOW ](
			context: ActionContext<Application, Application>,
			payload: string,
		): void {
			context.commit( SET_POPPER_VISIBLE, payload );
		},
		[ HELP_LINK_SET ](
			context: ActionContext<Application, Application>,
			payload: string,
		): void {
			context.commit( SET_HELP_LINK, payload );
		},
		[ FEEDBACK_LINK_SET ](
			context: ActionContext<Application, Application>,
			payload: string,
		): void {
			context.commit( SET_FEEDBACK_LINK, payload );
		},

	};
}
