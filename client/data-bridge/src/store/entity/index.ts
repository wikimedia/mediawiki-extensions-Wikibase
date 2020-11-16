import { EntityMutations } from '@/store/entity/mutations';
import { EntityActions } from '@/store/entity/actions';
import { Module } from 'vuex-smart-module';
import { EntityState } from './EntityState';

export const entityModule = new Module( {
	state: EntityState,
	mutations: EntityMutations,
	actions: EntityActions,
} );
