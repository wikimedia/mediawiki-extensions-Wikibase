import { ActionContext } from 'vuex';
import Application from '@/store/Application';
import {
	BRIDGE_INIT,
} from '@/store/actionTypes';
import {
	APPLICATION_STATUS_SET,
	EDITFLOW_SET,
	PROPERTY_TARGET_SET,
} from '@/store/mutationTypes';
import {
	NS_ENTITY,
} from '@/store/namespaces';
import {
	ENTITY_INIT,
} from '@/store/entity/actionTypes';
import namespacedStoreEvent from '@/store/namespacedStoreEvent';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import AppInformation from '@/definitions/AppInformation';
import { services } from '@/services';

export const actions = {
	[ BRIDGE_INIT ](
		context: ActionContext<Application, Application>,
	): Promise<void> {
		return services.getApplicationInformationRepository().getInformation().then(
			( information: AppInformation ) => {
				context.commit( EDITFLOW_SET, information.editFlow );
				context.commit( PROPERTY_TARGET_SET, information.propertyId );

				return context.dispatch(
					namespacedStoreEvent( NS_ENTITY, ENTITY_INIT ),
					{ entity: information.entityId },
				).then( () => {
					context.commit( APPLICATION_STATUS_SET, ApplicationStatus.READY );
				} ).catch( ( error ) => {
					context.commit( APPLICATION_STATUS_SET, ApplicationStatus.ERROR );
					// TODO: store information about the error somewhere and show it!
					throw error;
				} );
			},
		);
	},
};
