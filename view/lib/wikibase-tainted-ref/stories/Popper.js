import { storiesOf } from '@storybook/vue';
import Popper from '@/presentation/components/Popper.vue';
import { getters } from '@/store/getters';
import Vue from 'vue';
import Vuex from 'vuex';

Vue.use( Vuex );

storiesOf( 'Popper', module )
	.add( 'Popper component', () => ( {
		components: { Popper },
		store: new Vuex.Store( {
			state: { helpLink: 'https://test.invalid' },
			getters,
		} ),
		template:
			'<p><Popper></Popper></p>',
	} ) );
