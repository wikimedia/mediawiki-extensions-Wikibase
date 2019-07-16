import { GetterTree } from 'vuex';
import EntityState from '@/store/entity/EntityState';

export const getters: GetterTree<EntityState, any> = {
	id( state: EntityState ): string {
		return state.id;
	},

	revision( state: EntityState ): number {
		return state.baseRevision;
	},
};
