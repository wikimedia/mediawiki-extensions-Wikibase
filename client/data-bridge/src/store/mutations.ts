import { MutationTree } from 'vuex';
import Application from '@/store/Application';
import {
	PROPERTY_TARGET_SET,
	EDITFLOW_SET,
} from '@/store/mutationTypes';

export const mutations: MutationTree<Application> = {
	[ PROPERTY_TARGET_SET ]( state: Application, targetProperty: string ): void {
		state.targetProperty = targetProperty;
	},

	[ EDITFLOW_SET ]( state: Application, editFlow: string ): void {
		state.editFlow = editFlow;
	},
};
