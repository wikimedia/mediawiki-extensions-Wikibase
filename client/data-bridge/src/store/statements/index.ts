import { Module } from 'vuex-smart-module';
import { StatementMutations } from '@/store/statements/mutations';
import { StatementActions } from '@/store/statements/actions';
import { StatementGetters } from '@/store/statements/getters';
import { StatementState } from './StatementState';

export const statementModule = new Module( {
	state: StatementState,
	mutations: StatementMutations,
	actions: StatementActions,
	getters: StatementGetters,
} );
