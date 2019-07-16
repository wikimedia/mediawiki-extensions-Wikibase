import { GetterTree } from 'vuex';
import Application from '@/store/Application';

export const getters: GetterTree<Application, any> = {
	editFlow( state: Application ): string {
		return state.editFlow;
	},

	targetProperty( state: Application ): string {
		return state.targetProperty;
	},
};
