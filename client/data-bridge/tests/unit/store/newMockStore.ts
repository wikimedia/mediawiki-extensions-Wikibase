import { ActionContext, Commit, Dispatch, GetterTree } from 'vuex';

export default function newMockStore(
	{ commit, dispatch, state, getters, rootState, rootGetters }: {
		commit?: Commit,
		dispatch?: Dispatch,
		state?: any,
		getters?: GetterTree<any, any>,
		rootState?: any,
		rootGetters?: GetterTree<any, any>,
	},
): ActionContext<any, any> {
	return {
		commit: commit || jest.fn(),
		dispatch: dispatch || jest.fn(),
		state: state || {},
		getters: getters || {},
		rootState: rootState || {},
		rootGetters: rootGetters || {},
	};
}
