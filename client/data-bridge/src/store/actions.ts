import { ActionContext } from 'vuex';
import Application from '@/store/Application';
import {
	BRIDGE_INIT,
} from '@/store/actionTypes';
import {
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

export const actions = {
	[ BRIDGE_INIT ](
		context: ActionContext<Application, any>,
		payload: {
			editFlow: string,
			targetProperty: string,
			targetEntity: string,
		},
	): Promise<void> {
		context.commit( EDITFLOW_SET, payload.editFlow );
		context.commit( PROPERTY_TARGET_SET, payload.targetProperty );
		return Promise.resolve(
			context.dispatch(
				namespacedStoreEvent( NS_ENTITY, ENTITY_INIT ),
				payload.targetEntity,
			),
		);
	},
};
