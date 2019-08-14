import {
	ActionContext,
	ActionTree,
} from 'vuex';
import Application from '@/store/Application';
import EntityState from '@/store/entity/EntityState';
import {
	ENTITY_INIT,
} from '@/store/entity/actionTypes';
import {
	ENTITY_UPDATE,
	ENTITY_REVISION_UPDATE,
} from '@/store/entity/mutationTypes';
import EntityRepository from '@/definitions/data-access/EntityRepository';

export default function actions( entityRepository: EntityRepository ): ActionTree<EntityState, Application> {
	return {
		[ ENTITY_INIT ](
			context: ActionContext<EntityState, Application>,
			payload: { entity: string; revision?: number },
		): Promise<void> {
			return entityRepository
				.getEntity( payload.entity, payload.revision )
				.then( ( entity ) => {
					context.commit( ENTITY_REVISION_UPDATE, entity.revisionId );
					context.commit( ENTITY_UPDATE, entity.entity );
				} );
		},
	};
}
