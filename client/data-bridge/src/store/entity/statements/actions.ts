import { ActionContext } from 'vuex';
import Application from '@/store/Application';
import {
	STATEMENTS_INIT,
} from '@/store/entity/statements/actionTypes';
import {
	STATEMENTS_SET,
} from '@/store/entity/statements/mutationTypes';
import StatementsState from '@/store/entity/statements/StatementsState';
import StatementMap from '@/datamodel/StatementMap';

export const actions = {
	[ STATEMENTS_INIT ](
		context: ActionContext<StatementsState, Application>,
		payload: {
			entityId: string;
			statements: StatementMap;
		},
	): void {
		context.commit( STATEMENTS_SET, {
			entityId: payload.entityId,
			statements: payload.statements,
		} );
	},
};
