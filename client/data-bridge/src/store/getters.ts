import { GetterTree } from 'vuex';
import Status from '@/definitions/ApplicationStatus';
import DataValue from '@/datamodel/DataValue';
import Application from '@/store/Application';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import {
	ENTITY_ID,
} from '@/store/entity/getterTypes';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import namespacedStoreEvent from '@/store/namespacedStoreEvent';

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
	targetValue( state: Application, getters: { [ key: string ]: any } ): DataValue|null {
		if ( state.applicationStatus !== Status.READY ) {
			return null;
		}

		const entityId = getters[ namespacedStoreEvent( NS_ENTITY, ENTITY_ID ) ];
		const path = {
			entityId,
			propertyId: state.targetProperty,
			index: 0,
		};

		return getters[
			namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValue )
		]( path );
	},
};
