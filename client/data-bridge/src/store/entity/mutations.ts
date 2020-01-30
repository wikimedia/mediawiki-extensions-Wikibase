import { Mutations } from 'vuex-smart-module';
import { EntityState } from '@/store/entity';
import {
	ENTITY_UPDATE,
	ENTITY_REVISION_UPDATE,
} from '@/store/entity/mutationTypes';
import Entity from '@/datamodel/Entity';

export class EntityMutations extends Mutations<EntityState> {
	public [ ENTITY_UPDATE ]( entity: Entity ): void {
		this.state.id = entity.id;
	}

	public [ ENTITY_REVISION_UPDATE ]( revision: number ): void {
		this.state.baseRevision = revision;
	}
}
