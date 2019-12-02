import { storiesOf } from '@storybook/vue';
import Popper from '@/presentation/components/Popper.vue';
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

storiesOf( 'Popper', module )
	.add( 'Popper component', () => ( {
		components: { Popper },
		store: new Vuex.Store( {
			state: { helpLink: 'https://test.invalid' },
			getters,
		} ),
		template:
			'<p><Popper guid="a-guid"></Popper></p>',
	} ) );
