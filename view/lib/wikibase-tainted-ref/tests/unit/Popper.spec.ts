import { shallowMount } from '@vue/test-utils';
import { Plugin } from '@vue/runtime-core';
import { Store } from 'vuex';
import Message from '@/vue-plugins/Message';
import Application from '@/store/Application';
import Popper from '@/presentation/components/Popper.vue';
import { POPPER_HIDE } from '@/store/actionTypes';

const messagePlugin: [ Plugin, ...unknown[] ] = [ Message, { messageToTextFunction: () => {
	return 'dummy';
} } ];

function createStore(): Store<Partial<Application>> {
	return new Store<Partial<Application>>( {
		state: {},
	} );
}

describe( 'Popper.vue', () => {
	it( 'should render the Popper', () => {
		const store = createStore();
		const wrapper = shallowMount( Popper as any, {
			global: { plugins: [ store, messagePlugin ] },
		} );
		expect( wrapper.classes() ).toContain( 'wb-tr-popper-wrapper' );
	} );
	it( 'closes the popper when the x is clicked', () => {
		const store = createStore();
		store.dispatch = jest.fn();

		const wrapper = shallowMount( Popper as any, {
			global: { plugins: [ store, messagePlugin ] },
			props: { guid: 'a-guid' },
		} );
		wrapper.find( '.wb-tr-popper-close' ).trigger( 'click' );
		expect( store.dispatch ).toHaveBeenCalledWith( POPPER_HIDE, 'a-guid' );
	} );
	it( 'closes the popper when the focus is lost', () => {
		const store = createStore();
		store.dispatch = jest.fn();

		const wrapper = shallowMount( Popper as any, {
			global: { plugins: [ store, messagePlugin ] },
			props: { guid: 'a-guid' },
		} );
		wrapper.trigger( 'focusout' );
		expect( store.dispatch ).toHaveBeenCalledWith( POPPER_HIDE, 'a-guid' );
	} );
	it( 'should use injected title text', () => {
		const store = createStore();
		store.dispatch = jest.fn();
		const messageToTextFunction = ( key: any ): string => `(${key})`;
		const wrapper = shallowMount( Popper as any, {
			global: {
				plugins: [
					store,
					[ Message, { messageToTextFunction } ],
				],
			},
			props: { guid: 'a-guid', title: 'kitten' },
		} );

		expect( wrapper.find( '.wb-tr-popper-title' ).element.textContent )
			.toMatch( 'kitten' );
	} );
	it( 'should display the injected slots', () => {
		const store = createStore();
		store.dispatch = jest.fn();
		const messageToTextFunction = ( key: any ): string => `(${key})`;
		const wrapper = shallowMount( Popper as any, {
			global: {
				plugins: [
					store,
					[ Message, { messageToTextFunction } ],
				],
			},
			props: { guid: 'a-guid', title: 'title' },
			slots: {
				'subheading-area': '<div class="the-subheading">subhead</div>',
				content: '<div class="the-content">content</div>',
			},
		} );

		expect( wrapper.find( '.the-subheading' ).element.textContent )
			.toMatch( 'subhead' );
		expect( wrapper.find( '.the-content' ).element.textContent )
			.toMatch( 'content' );
	} );

} );
