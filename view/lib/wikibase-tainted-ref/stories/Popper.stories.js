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

export default { title: 'Popper' };

export function popperComponent() {
	return {
		components: { Popper },
		store: new Vuex.Store( {
			state: { helpLink: 'https://test.invalid' },
			getters,
		} ),
		template:
			'<p><Popper guid="a-guid" title="Some Title">' +
			'<template v-slot:subheading-area><a href="#">Some Subheading Link</a></template>' +
			'<template v-slot:content>' +
			'<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore' +
			'dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip' +
			'ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu ' +
			'fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia ' +
			'deserunt mollit anim id est laborum.</p>' +
			'<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore' +
			'dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip' +
			'ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu ' +
			'fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia ' +
			'deserunt mollit anim id est laborum.</p>' +
			'</template>' +
			'</Popper></p>',
	};
}
