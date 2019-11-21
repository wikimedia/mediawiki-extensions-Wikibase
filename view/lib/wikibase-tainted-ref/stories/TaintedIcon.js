import { storiesOf } from '@storybook/vue';
import TaintedIcon from '@/presentation/components/TaintedIcon.vue';
import { getters } from '@/store/getters';
import Vue from 'vue';
import Vuex from 'vuex';
import Track from '@/vue-plugins/Track';

Vue.use( Vuex );
// eslint-disable-next-line no-console
Vue.use( Track, { trackingFunction: console.log } );

storiesOf( 'TaintedIcon', module )
	.add( 'Just the icon', () => ( {
		components: { TaintedIcon },
		store: new Vuex.Store( {
			state: { statementsPopperIsOpen: { 'a-guid': false } },
			getters,
		} ),
		template:
			'<p><TaintedIcon guid="a-guid"></TaintedIcon></p>',
	} ) );
