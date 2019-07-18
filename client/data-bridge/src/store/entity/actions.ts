import { ActionContext } from 'vuex';
import Application from '@/store/Application';
import EntityState from '@/store/entity/EntityState';
import {
	ENTITY_INIT,
} from '@/store/entity/actionTypes';
import {
	ENTITY_UPDATE,
	ENTITY_REVISION_UPDATE,
} from '@/store/entity/mutationTypes';
import { services } from '@/services';

export const actions = {
	[ ENTITY_INIT ](
		context: ActionContext<EntityState, Application>,
		payload: { entity: string; revision?: number },
	): Promise<void> {
		return Promise.resolve(
			services.getEntityRepository().getEntity( payload.entity, payload.revision ),
		).then( ( entity ) => {
			context.commit( ENTITY_REVISION_UPDATE, entity.revisionId );
			context.commit( ENTITY_UPDATE, entity.entity );
		} );
	},
};
