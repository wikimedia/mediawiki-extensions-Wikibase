import TaintedPopper from '@/presentation/components/TaintedPopper.vue';
import { getters } from '@/store/getters';
import Vue from 'vue';
import Vuex from 'vuex';
import Track from '@/vue-plugins/Track';
import Message from '@/vue-plugins/Message';

Vue.use( Vuex );
// eslint-disable-next-line no-console
Vue.use( Track, { trackingFunction: console.log } );
Vue.use( Message, { messageToTextFunction: ( key ) => {
	return `(${key})`;
} } );

export default { title: 'TaintedPopper' };

export function popperComponent() {
	return {
		components: { TaintedPopper },
		store: new Vuex.Store( {
			state: { helpLink: 'https://test.invalid' },
			getters,
		} ),
		template:
			'<p><TaintedPopper guid="a-guid"></TaintedPopper></p>',
	};
}
