import { ActionContext, Commit, Dispatch } from 'vuex';

export default function newMockStore(
	{ commit, dispatch, state, getters, rootState, rootGetters }: {
		commit?: Commit;
		dispatch?: Dispatch;
		state?: any;
		getters?: object;
		rootState?: any;
		rootGetters?: object;
	},
): ActionContext<any, any> {
	return {
		commit: commit || jest.fn(),
		dispatch: dispatch || jest.fn( () => Promise.resolve() ),
		state: state || {},
		getters: getters || {},
		rootState: rootState || {},
		rootGetters: rootGetters || {},
	};
}
