import Popper from '@/presentation/components/Popper.vue';
import { getters } from '@/store/getters';
import { app } from '@storybook/vue3';
import Vuex from 'vuex';
import Track from '@/vue-plugins/Track';
import Message from '@/vue-plugins/Message';

export default {
	title: 'Popper',
	component: Popper,
};

export function popperComponent() {
	app.use( new Vuex.Store( {
		state() {
			return { helpLink: 'https://test.invalid' };
		},
		getters,
		actions: { hidePopper() { /* do nothing */ } },
	} ) );
	// eslint-disable-next-line no-console
	app.use( Track, { trackingFunction: console.log } );
	app.use( Message, { messageToTextFunction: ( key ) => {
		return `(${key})`;
	} } );

	return {
		components: { Popper },
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
