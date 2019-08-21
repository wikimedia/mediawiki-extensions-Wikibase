import {
	ActionContext,
} from 'vuex';
import Application from '@/store/Application';
import {
	BRIDGE_INIT,
} from '@/store/actionTypes';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import AppInformation from '@/definitions/AppInformation';
import {
	APPLICATION_STATUS_SET,
	EDITFLOW_SET,
	PROPERTY_TARGET_SET,
} from '@/store/mutationTypes';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import {
	STATEMENTS_IS_AMBIGUOUS,
	STATEMENTS_PROPERTY_EXISTS,
} from '@/store/entity/statements/getterTypes';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import {
	ENTITY_ID,
} from '@/store/entity/getterTypes';
import {
	ENTITY_INIT,
} from '@/store/entity/actionTypes';
import namespacedStoreEvent from '@/store/namespacedStoreEvent';

export const actions = {
	[ BRIDGE_INIT ](
		context: ActionContext<Application, Application>,
		information: AppInformation,
	): Promise<void> {
		context.commit( EDITFLOW_SET, information.editFlow );
		context.commit( PROPERTY_TARGET_SET, information.propertyId );

		return context.dispatch(
			namespacedStoreEvent( NS_ENTITY, ENTITY_INIT ),
			{ entity: information.entityId },
		).then( () => {
			const entityId = context.getters[ namespacedStoreEvent( NS_ENTITY, ENTITY_ID ) ];
			const path = {
				entityId,
				propertyId: context.state.targetProperty,
				index: 0,
			};

			if ( context.getters[
				namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, STATEMENTS_PROPERTY_EXISTS )
			]( entityId, context.state.targetProperty ) === false
			) {
				context.commit( APPLICATION_STATUS_SET, ApplicationStatus.ERROR );
				// TODO: store information about the error somewhere and show it!
			} else if ( context.getters[
				namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, STATEMENTS_IS_AMBIGUOUS )
			]( entityId, context.state.targetProperty ) === true
			) {
				context.commit( APPLICATION_STATUS_SET, ApplicationStatus.ERROR );
				// TODO: store information about the error somewhere and show it!
			} else if ( context.getters[
				namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.snakType )
			]( path ) !== 'value'
			) {
				context.commit( APPLICATION_STATUS_SET, ApplicationStatus.ERROR );
				// TODO: store information about the error somewhere and show it!
			} else if ( context.getters[
				namespacedStoreEvent( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValueType )
			]( path ) !== 'string'
			) {
				context.commit( APPLICATION_STATUS_SET, ApplicationStatus.ERROR );
				// TODO: store information about the error somewhere and show it!
			} else {
				context.commit( APPLICATION_STATUS_SET, ApplicationStatus.READY );
			}

		} ).catch( ( error ) => {
			context.commit( APPLICATION_STATUS_SET, ApplicationStatus.ERROR );
			// TODO: store information about the error somewhere and show it!
			throw error;
		} );
	},
};
