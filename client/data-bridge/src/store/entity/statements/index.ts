import { Module } from 'vuex';
import Application from '@/store/Application';
import StatementsState from '@/store/entity/statements/StatementsState';
import { mutations } from '@/store/entity/statements/mutations';
import { getters } from '@/store/entity/statements/getters';
import { actions } from '@/store/entity/statements/actions';

export default function (): Module<StatementsState, Application> {
	const state: StatementsState = {};

	return {
		namespaced: true,
		state,
		mutations,
		getters,
		actions,
	};
}
