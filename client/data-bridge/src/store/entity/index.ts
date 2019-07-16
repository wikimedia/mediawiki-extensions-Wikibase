import { Module } from 'vuex';
import EntityState from '@/store/entity/EntityState';

export default function (): Module<EntityState, any> {
	const state: EntityState = {
		id: '',
		baseRevision: 0,
	};

	const namespaced = true;

	return {
		namespaced,
		state,
	};
}
