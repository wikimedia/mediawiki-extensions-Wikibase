import { EntityMutations } from '@/store/entity/mutations';
import { EntityActions } from '@/store/entity/actions';
import { Module } from 'vuex-smart-module';
import EntityId from '@/datamodel/EntityId';

export class EntityState {
	public id: EntityId = '';
	public baseRevision = 0;
}

export const entityModule = new Module( {
	state: EntityState,
	mutations: EntityMutations,
	actions: EntityActions,
} );
