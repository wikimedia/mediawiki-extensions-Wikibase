import TaintedIcon from '@/presentation/components/TaintedIcon.vue';
import { getters } from '@/store/getters';
import Vue from 'vue';
import Vuex from 'vuex';
import Track from '@/vue-plugins/Track';

Vue.use( Vuex );
// eslint-disable-next-line no-console
Vue.use( Track, { trackingFunction: console.log } );

export default { title: 'TaintedIcon' };

export function justTheIcon() {
	return {
		components: { TaintedIcon },
		store: new Vuex.Store( {
			state: { statementsPopperIsOpen: { 'a-guid': false } },
			getters,
		} ),
		template:
			'<p><TaintedIcon guid="a-guid"></TaintedIcon></p>',
	};
}
