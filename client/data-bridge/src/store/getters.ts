import { GetterTree } from 'vuex';
import Application from '@/store/Application';
import ApplicationStatus from '@/store/ApplicationStatus';

export const getters: GetterTree<Application, any> = {
	editFlow( state: Application ): string {
		return state.editFlow;
	},

	targetProperty( state: Application ): string {
		return state.targetProperty;
	},

	applicationStatus( state: Application ): ApplicationStatus {
		return state.applicationStatus;
	},
};
