import { Store } from 'vuex';
import Application from '@/store/Application';
import { RootActions } from '@/store/actions';
import { RootGetters } from '@/store/getters';
import { RootMutations } from '@/store/mutations';
import { BaseState } from './BaseState';
import { createStore as smartCreateStore, Module } from 'vuex-smart-module';
import ServiceContainer from '@/services/ServiceContainer';
import { NS_ENTITY, NS_STATEMENTS } from '@/store/namespaces';
import { entityModule } from './entity';
import { statementModule } from '@/store/statements';
import mutationsTrackerPlugin from '@/tracking/mutationsTrackerPlugin';

export const rootModule = new Module( {
	state: BaseState,
	getters: RootGetters,
	mutations: RootMutations,
	actions: RootActions,
	modules: {
		[ NS_ENTITY ]: entityModule,
		[ NS_STATEMENTS ]: statementModule,
	},
} );

export function createStore( services: ServiceContainer ): Store<Application> {

	const store = smartCreateStore( rootModule, {
		strict: process.env.NODE_ENV !== 'production',
		plugins: [ mutationsTrackerPlugin( services.get( 'tracker' ) ) ],
	} );

	store.$services = services;
	return store;
}
