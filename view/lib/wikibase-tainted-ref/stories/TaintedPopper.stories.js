import TaintedPopper from '@/presentation/components/TaintedPopper.vue';
import { getters } from '@/store/getters';
import { app } from '@storybook/vue3';
import Vuex from 'vuex';
import Track from '@/vue-plugins/Track';
import Message from '@/vue-plugins/Message';

export default {
	title: 'TaintedPopper',
	component: TaintedPopper,
};

export function popperComponent() {
	app.use( new Vuex.Store( {
		state() {
			return { helpLink: 'https://test.invalid' };
		},
		getters,
	} ) );
	// eslint-disable-next-line no-console
	app.use( Track, { trackingFunction: console.log } );
	app.use( Message, { messageToTextFunction: ( key ) => {
		return `(${key})`;
	} } );

	return {
		components: { TaintedPopper },
		template:
			'<p><TaintedPopper guid="a-guid"></TaintedPopper></p>',
	};
}
