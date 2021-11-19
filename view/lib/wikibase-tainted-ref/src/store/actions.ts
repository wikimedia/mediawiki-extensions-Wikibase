import { TrackFunction } from '@/@types/TrackingOptions';
import {
	POPPER_HIDE, POPPER_SHOW,
	STORE_INIT,
	STATEMENT_TAINTED_STATE_TAINT,
	STATEMENT_TAINTED_STATE_UNTAINT,
	HELP_LINK_SET, START_EDIT, STOP_EDIT,
} from '@/store/actionTypes';
import Application from './Application';
import { ActionContext, ActionTree } from 'vuex';
import {
	SET_ALL_UNTAINTED,
	SET_ALL_POPPERS_HIDDEN,
	SET_ALL_EDIT_MODE_FALSE,
	SET_POPPER_HIDDEN,
	SET_POPPER_VISIBLE,
	SET_TAINTED,
	SET_UNTAINTED,
	SET_HELP_LINK,
	SET_STATEMENT_EDIT_TRUE,
	SET_STATEMENT_EDIT_FALSE,
} from '@/store/mutationTypes';
import { GET_STATEMENT_TAINTED_STATE } from '@/store/getterTypes';

export default function actions( metricTracker: TrackFunction ): ActionTree<Application, Application> {
	return {
		[ STORE_INIT ](
			context: ActionContext<Application, Application>,
			guids: string[],
		): void {
			context.commit( SET_ALL_UNTAINTED, guids );
			context.commit( SET_ALL_POPPERS_HIDDEN, guids );
			context.commit( SET_ALL_EDIT_MODE_FALSE, guids );
		},
		[ STATEMENT_TAINTED_STATE_UNTAINT ](
			context: ActionContext<Application, Application>,
			guid: string,
		): void {
			context.commit( SET_UNTAINTED, guid );
			context.commit( SET_POPPER_HIDDEN, guid );
		},
		[ STATEMENT_TAINTED_STATE_TAINT ](
			context: ActionContext<Application, Application>,
			guid: string,
		): void {
			context.commit( SET_TAINTED, guid );
		},
		[ START_EDIT ](
			context: ActionContext<Application, Application>,
			guid: string,
		): void {
			context.commit( SET_STATEMENT_EDIT_TRUE, guid );
			context.commit( SET_POPPER_HIDDEN, guid );
			if ( context.getters[ GET_STATEMENT_TAINTED_STATE ]( guid ) ) {
				metricTracker( 'counter.wikibase.view.tainted-ref.startedEditWithTaintedIcon', 1 );
			}
		},
		[ STOP_EDIT ](
			context: ActionContext<Application, Application>,
			guid: string,
		): void {
			context.commit( SET_STATEMENT_EDIT_FALSE, guid );
		},
		[ POPPER_HIDE ](
			context: ActionContext<Application, Application>,
			guid: string,
		): void {
			context.commit( SET_POPPER_HIDDEN, guid );
		},
		[ POPPER_SHOW ](
			context: ActionContext<Application, Application>,
			guid: string,
		): void {
			context.commit( SET_POPPER_VISIBLE, guid );
		},
		[ HELP_LINK_SET ](
			context: ActionContext<Application, Application>,
			url: string,
		): void {
			context.commit( SET_HELP_LINK, url );
		},
	};
}
