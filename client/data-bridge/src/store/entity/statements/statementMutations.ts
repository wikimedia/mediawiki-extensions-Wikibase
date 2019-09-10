import Vue from 'vue';
import { MutationTree } from 'vuex';
import StatementsState from '@/store/entity/statements/StatementsState';
import StatementMap from '@/datamodel/StatementMap';
import {
	STATEMENTS_SET,
} from '@/store/entity/statements/mutationTypes';
import EntityId from '@/datamodel/EntityId';

export const statementMutations: MutationTree<StatementsState> = {
	[ STATEMENTS_SET ](
		state: StatementsState,
		payload: { entityId: EntityId; statements: StatementMap },
	): void {
		Vue.set( state, payload.entityId, payload.statements );
	},
};
