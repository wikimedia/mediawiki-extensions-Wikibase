import TaintedIcon from '@/presentation/components/TaintedIcon.vue';
import { getters } from '@/store/getters';
import { app } from '@storybook/vue3';
import Vuex from 'vuex';
import Track from '@/vue-plugins/Track';

export default {
	title: 'TaintedIcon',
	component: TaintedIcon,
};

export function justTheIcon() {
	app.use( new Vuex.Store( {
		state() {
			return { statementsPopperIsOpen: { 'a-guid': false } };
		},
		getters,
	} ) );
	// eslint-disable-next-line no-console
	app.use( Track, { trackingFunction: console.log } );

	return {
		components: { TaintedIcon },
		template:
			'<p><TaintedIcon guid="a-guid"></TaintedIcon></p>',
	};
}
