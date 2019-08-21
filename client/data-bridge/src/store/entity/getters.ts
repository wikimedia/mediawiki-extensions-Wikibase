import { GetterTree } from 'vuex';
import Application from '@/store/Application';
import EntityState from '@/store/entity/EntityState';
import {
	ENTITY_ID,
	ENTITY_REVISION,
} from '@/store/entity/getterTypes';

export const getters: GetterTree<EntityState, Application> = {
	[ ENTITY_ID ]( state: EntityState ): string {
		return state.id;
	},

	[ ENTITY_REVISION ]( state: EntityState ): number {
		return state.baseRevision;
	},
};
