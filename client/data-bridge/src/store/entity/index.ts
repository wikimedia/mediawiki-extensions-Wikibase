import { Module } from 'vuex';
import EntityState from '@/store/entity/EntityState';
import { mutations } from '@/store/entity/mutations';
import { getters } from '@/store/entity/getters';

export default function (): Module<EntityState, any> {
	const state: EntityState = {
		id: '',
		baseRevision: 0,
	};

	const namespaced = true;

	return {
		namespaced,
		state,
		getters,
		mutations,
	};
}
