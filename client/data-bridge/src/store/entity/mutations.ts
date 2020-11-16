import { Mutations } from 'vuex-smart-module';
import { EntityState } from './EntityState';
import Entity from '@/datamodel/Entity';

export class EntityMutations extends Mutations<EntityState> {
	public updateEntity( entity: Entity ): void {
		this.state.id = entity.id;
	}

	public updateRevision( revision: number ): void {
		this.state.baseRevision = revision;
	}

	public reset(): void {
		// this.state already has all the properties, and Object.assign() invokes setters, so this is reactive
		Object.assign( this.state, new EntityState() );
	}
}
