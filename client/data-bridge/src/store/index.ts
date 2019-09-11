import Vue from 'vue';
import Vuex, { Store, StoreOptions } from 'vuex';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import Application from '@/store/Application';
import actions from '@/store/actions';
import { getters } from '@/store/getters';
import { mutations } from '@/store/mutations';
import createEntity from './entity';
import {
	NS_ENTITY,
} from '@/store/namespaces';
import ServiceRepositories from '@/services/ServiceRepositories';

Vue.use( Vuex );

export function createStore( services: ServiceRepositories ): Store<Application> {
	const state: Application = {
		targetLabel: null,
		targetProperty: '',
		editFlow: '',
		applicationStatus: ApplicationStatus.INITIALIZING,
	};

	const storeBundle: StoreOptions<Application> = {
		state,
		actions: actions( services.getEntityLabelRepository() ),
		getters,
		mutations,
		strict: process.env.NODE_ENV !== 'production',
		modules: {
			[ NS_ENTITY ]: createEntity(
				services.getReadingEntityRepository(),
				services.getWritingEntityRepository(),
			),
		},
	};

	return new Store<Application>( storeBundle );
}
