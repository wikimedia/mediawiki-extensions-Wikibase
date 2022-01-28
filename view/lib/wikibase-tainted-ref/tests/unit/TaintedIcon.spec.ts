import { Plugin } from '@vue/runtime-core';
import { shallowMount } from '@vue/test-utils';
import { Store } from 'vuex';
import Application from '@/store/Application';
import TaintedIcon from '@/presentation/components/TaintedIcon.vue';
import { POPPER_SHOW } from '@/store/actionTypes';
import Track from '@/vue-plugins/Track';
import Message from '@/vue-plugins/Message';
import { GET_POPPER_STATE } from '@/store/getterTypes';

const trackPlugin: [ Plugin, ...unknown[] ] = [ Track, { trackingFunction: () => {
	// do nothing on track
} } ];
const messagePlugin: [ Plugin, ...unknown[] ] = [ Message, { messageToTextFunction: () => {
	return 'dummy';
} } ];
const plugins = [ trackPlugin, messagePlugin ];

function createStore( popperOpenState = false ): Store<Partial<Application>> {
	return new Store<Partial<Application>>( {
		state: {},
		getters: {
			[ GET_POPPER_STATE ]: () => () => popperOpenState,
		},
		actions: {
			[ POPPER_SHOW ]: jest.fn(),
		},
	} );
}

describe( 'TaintedIcon.vue', () => {
	it( 'should render the icon', () => {
		const store = createStore();
		const wrapper = shallowMount( TaintedIcon as any, {
			global: { plugins: [ store, ...plugins ] },
		} );
		expect( wrapper.element.tagName ).toEqual( 'A' );
		expect( wrapper.classes() ).toContain( 'wb-tr-tainted-icon' );
	} );
	it( 'opens the popper on click', () => {
		const store = createStore();
		store.dispatch = jest.fn();

		const wrapper = shallowMount( TaintedIcon as any, {
			global: { plugins: [ store, ...plugins ] },
			props: { guid: 'a-guid' },
		} );
		wrapper.trigger( 'click' );
		expect( store.dispatch ).toHaveBeenCalledWith( POPPER_SHOW, 'a-guid' );
	} );
	it( 'is an un-clickable div if the popper is open', () => {
		const store = createStore( true );

		store.dispatch = jest.fn();
		const wrapper = shallowMount( TaintedIcon as any, {
			global: { plugins: [ store, ...plugins ] },
			props: { guid: 'a-guid' },
		} );

		expect( wrapper.element.tagName ).toEqual( 'DIV' );
		expect( wrapper.classes() ).toContain( 'wb-tr-tainted-icon' );

		wrapper.trigger( 'click' );
		expect( store.dispatch ).not.toHaveBeenCalledWith( POPPER_SHOW, 'a-guid' );
	} );
	it( 'clicking the tainted icon triggers a tracking event', () => {
		const trackingFunction = jest.fn();
		const store = createStore();
		const wrapper = shallowMount( TaintedIcon as any, {
			global: { plugins: [ store, [ Track, { trackingFunction } ], messagePlugin ] },
		} );
		wrapper.trigger( 'click' );
		expect( trackingFunction ).toHaveBeenCalledWith( 'counter.wikibase.view.tainted-ref.taintedIconClick', 1 );
	} );
	it( 'uses the title text from the message plugin', () => {
		const messageToTextFunction = jest.fn();
		messageToTextFunction.mockReturnValue( 'DUMMY_TEXT' );
		const store = createStore();
		const wrapper = shallowMount( TaintedIcon as any, {
			global: { plugins: [ store, [ Message, { messageToTextFunction } ], trackPlugin ] },
		} );
		expect( ( wrapper.find( '.wb-tr-tainted-icon' ).element as HTMLElement ).title ).toMatch( 'DUMMY_TEXT' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-tainted-icon-title' );
	} );
} );
