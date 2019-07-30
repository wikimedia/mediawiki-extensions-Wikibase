import { GetterTree } from 'vuex';
import Application from '@/store/Application';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import { NS_ENTITY } from '@/store/namespaces';
import namespacedStoreEvent from '@/store/namespacedStoreEvent';
import { ENTITY_ONLY_MAIN_STRING_VALUE } from '@/store/entity/getterTypes';

export const getters: GetterTree<Application, Application> = {
	editFlow( state: Application ): string {
		return state.editFlow;
	},

	targetProperty( state: Application ): string {
		return state.targetProperty;
	},

	applicationStatus( state: Application ): ApplicationStatus {
		return state.applicationStatus;
	},

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	targetValue( _state: Application, getters: any ): string|null {
		const getter: ( propertyId: string ) => string|null
			= getters[ namespacedStoreEvent( NS_ENTITY, ENTITY_ONLY_MAIN_STRING_VALUE ) ];
		return getter( getters.targetProperty );
	},
};
