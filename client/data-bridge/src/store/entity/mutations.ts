import { MutationTree } from 'vuex';
import EntityState from '@/store/entity/EntityState';
import {
	ENTITY_UPDATE,
	ENTITY_REVISION_UPDATE,
} from '@/store/entity/mutationTypes';
import Entity from '@/datamodel/Entity';

export const mutations: MutationTree<EntityState> = {
	[ ENTITY_UPDATE ]( state: EntityState, entity: Entity ): void {
		state.id = entity.id;
	},

	[ ENTITY_REVISION_UPDATE ]( state: EntityState, revision: number ) {
		state.baseRevision = revision;
	},
};
