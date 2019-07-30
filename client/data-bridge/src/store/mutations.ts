import { MutationTree } from 'vuex';
import Application from '@/store/Application';
import {
	PROPERTY_TARGET_SET,
	EDITFLOW_SET,
	APPLICATION_STATUS_SET,
} from '@/store/mutationTypes';
import ApplicationStatus from '@/definitions/ApplicationStatus';

export const mutations: MutationTree<Application> = {
	[ PROPERTY_TARGET_SET ]( state: Application, targetProperty: string ): void {
		state.targetProperty = targetProperty;
	},

	[ EDITFLOW_SET ]( state: Application, editFlow: string ): void {
		state.editFlow = editFlow;
	},

	[ APPLICATION_STATUS_SET ]( state: Application, status: ApplicationStatus ): void {
		state.applicationStatus = status;
	},
};
