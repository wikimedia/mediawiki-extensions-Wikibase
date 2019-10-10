import { GetterTree } from 'vuex';
import Application from '@/store/Application';

export const getters: GetterTree<Application, Application> = {
	statementsTaintedState( state: Application ): Function {
		return ( guid: string ): boolean => {
			return state.statementsTaintedState[ guid ];
		};
	},
};
