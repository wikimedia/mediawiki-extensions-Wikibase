import { Module } from 'vuex';
import Application from '@/store/Application';
import EntityState from '@/store/entity/EntityState';
import { mutations } from '@/store/entity/mutations';
import { getters } from '@/store/entity/getters';
import { actions } from '@/store/entity/actions';

export default function (): Module<EntityState, Application> {
	const state: EntityState = {
		id: '',
		baseRevision: 0,
		statements: null,
	};

	const namespaced = true;

	return {
		namespaced,
		state,
		getters,
		mutations,
		actions,
	};
}
