import EntityRevision from '@/datamodel/EntityRevision';
import WritingEntityRepository from '@/definitions/data-access/WritingEntityRepository';
import {
	ENTITY_ID,
	ENTITY_REVISION,
} from '@/store/entity/getterTypes';
import { STATEMENTS_MAP } from '@/store/entity/statements/getterTypes';
import {
	ActionContext,
	ActionTree,
} from 'vuex';
import Application from '@/store/Application';
import EntityState from '@/store/entity/EntityState';
import {
	ENTITY_INIT,
	ENTITY_SAVE,
} from '@/store/entity/actionTypes';
import {
	ENTITY_UPDATE,
	ENTITY_REVISION_UPDATE,
} from '@/store/entity/mutationTypes';
import EntityRepository from '@/definitions/data-access/EntityRepository';
import {
	NS_STATEMENTS,
} from '@/store/namespaces';
import {
	STATEMENTS_INIT,
} from '@/store/entity/statements/actionTypes';
import namespacedStoreEvent from '@/store/namespacedStoreEvent';

export default function actions(
	entityRepository: EntityRepository,
	writingEntityRepository: WritingEntityRepository,
): ActionTree<EntityState, Application> {

	function updateEntity(
		context: ActionContext<EntityState, Application>,
		entity: EntityRevision,
	): Promise<unknown> {
		context.commit( ENTITY_REVISION_UPDATE, entity.revisionId );
		context.commit( ENTITY_UPDATE, entity.entity );
		return context.dispatch(
			namespacedStoreEvent( NS_STATEMENTS, STATEMENTS_INIT ),
			{
				entityId: entity.entity.id,
				statements: entity.entity.statements,
			},
		);
	}

	return {
		[ ENTITY_INIT ](
			context: ActionContext<EntityState, Application>,
			payload: { entity: string; revision?: number },
		): Promise<unknown> {
			return entityRepository
				.getEntity( payload.entity, payload.revision )
				.then( ( entity ) => updateEntity( context, entity ) );
		},

		[ ENTITY_SAVE ](
			context: ActionContext<EntityState, Application>,
		): Promise<unknown> {
			const entityId = context.getters[ ENTITY_ID ],
				entityRevision = new EntityRevision(
					{
						id: entityId,
						statements: context.getters[
							namespacedStoreEvent( NS_STATEMENTS, STATEMENTS_MAP )
						]( entityId ),
					},
					context.getters[ ENTITY_REVISION ],
				);

			return writingEntityRepository
				.saveEntity( entityRevision )
				.then( ( entity ) => updateEntity( context, entity ) );
		},
	};
}
