import { ActionContext } from 'vuex';
import Application from '@/store/Application';
import {
	BRIDGE_INIT,
} from '@/store/actionTypes';
import {
	EDITFLOW_SET,
	PROPERTY_TARGET_SET,
} from '@/store/mutationTypes';

export const actions = {
	[ BRIDGE_INIT ](
		context: ActionContext<Application, any>,
		payload: { editFlow: string, targetProperty: string },
	): void {
		context.commit( EDITFLOW_SET, payload.editFlow );
		context.commit( PROPERTY_TARGET_SET, payload.targetProperty );
	},
};
