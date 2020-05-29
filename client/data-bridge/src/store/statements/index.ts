import { StatementMap } from '@wmde/wikibase-datamodel-types';
import { Module } from 'vuex-smart-module';
import { StatementMutations } from '@/store/statements/mutations';
import { StatementActions } from '@/store/statements/actions';
import { StatementGetters } from '@/store/statements/getters';

export class StatementState {
	[ entityId: string ]: StatementMap;
}

export const statementModule = new Module( {
	state: StatementState,
	mutations: StatementMutations,
	actions: StatementActions,
	getters: StatementGetters,
} );
