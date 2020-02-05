import { Mutations } from 'vuex-smart-module';
import { EntityState } from '@/store/entity';
import Entity from '@/datamodel/Entity';

export class EntityMutations extends Mutations<EntityState> {
	public updateEntity( entity: Entity ): void {
		this.state.id = entity.id;
	}

	public updateRevision( revision: number ): void {
		this.state.baseRevision = revision;
	}
}
