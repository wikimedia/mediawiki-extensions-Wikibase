import { Plugin } from '@vue/runtime-core';
import { mount } from '@vue/test-utils';
import { Store } from 'vuex';
import Track from '@/vue-plugins/Track';
import Message from '@/vue-plugins/Message';
import Application from '@/store/Application';
import TaintedPopper from '@/presentation/components/TaintedPopper.vue';
import { POPPER_HIDE, STATEMENT_TAINTED_STATE_UNTAINT } from '@/store/actionTypes';
import { GET_HELP_LINK } from '@/store/getterTypes';

const trackingFunction: any = jest.fn();
const messagePlugin: [ Plugin, ...unknown[] ] = [ Message, { messageToTextFunction: () => {
	return 'dummy';
} } ];
const trackPlugin: [ Plugin, ...unknown[] ] = [ Track, { trackingFunction } ];
const plugins = [ messagePlugin, trackPlugin ];

function createMockStore( helpLink?: string ): Store<Partial<Application>> {
	return new Store<Partial<Application>>( {
		actions: {
			[ STATEMENT_TAINTED_STATE_UNTAINT ]: jest.fn(),
		},
		getters: {
			[ GET_HELP_LINK ]: helpLink ? () => helpLink : jest.fn(),
		},
	} );
}

describe( 'TaintedPopper.vue', () => {
	it( 'sets the help link according to the store', () => {
		const helpLinkUrl = 'https://wdtest/Help';
		const store = createMockStore( helpLinkUrl );
		const wrapper = mount( TaintedPopper as any, {
			global: { plugins: [ store, ...plugins ] },
		} );
		expect( wrapper.find( '.wb-tr-popper-help a' ).attributes().href ).toEqual( helpLinkUrl );
	} );
	it( 'clicking the help link triggers a tracking event', () => {
		const store = createMockStore();
		const wrapper = mount( TaintedPopper as any, {
			global: { plugins: [ store, ...plugins ] },
		} );
		wrapper.find( '.wb-tr-popper-help a' ).trigger( 'click' );
		expect( trackingFunction ).toHaveBeenCalledWith( 'counter.wikibase.view.tainted-ref.helpLinkClick', 1 );
	} );
	it( 'does not close the popper when the help link is focused', () => {
		const store = createMockStore();
		store.dispatch = jest.fn();

		const wrapper = mount( TaintedPopper as any, {
			global: { plugins: [ store, ...plugins ] },
			props: { guid: 'a-guid' },
		} );
		wrapper.trigger(
			'focusout', {
				relatedTarget: wrapper.find( '.wb-tr-popper-help' ).element,
			},
		);
		expect( store.dispatch ).not.toHaveBeenCalledWith( POPPER_HIDE, 'a-guid' );
	} );
	it( 'clicking the remove warning button untaints the statements', () => {
		const store = createMockStore();
		store.dispatch = jest.fn();

		const wrapper = mount( TaintedPopper as any, {
			global: { plugins: [ store, ...plugins ] },
			props: { guid: 'a-guid' },
		} );
		wrapper.find( '.wb-tr-popper-remove-warning' ).trigger( 'click' );
		expect( store.dispatch ).toHaveBeenCalledWith( STATEMENT_TAINTED_STATE_UNTAINT, 'a-guid' );
	} );
	it( 'clicking the remove warning button triggers a tracking event', () => {
		const store = createMockStore();
		const wrapper = mount( TaintedPopper as any, {
			global: { plugins: [ store, ...plugins ] },
		} );
		wrapper.find( '.wb-tr-popper-remove-warning' ).trigger( 'click' );
		expect( trackingFunction ).toHaveBeenCalledWith( 'counter.wikibase.view.tainted-ref.removeWarningClick', 1 );
	} );
	it( 'popper texts are taken from our Vue message plugin', () => {
		const messageToTextFunction = jest.fn();
		messageToTextFunction.mockImplementation( ( key ) => `(${key})` );

		const store = createMockStore();
		store.dispatch = jest.fn();

		const wrapper = mount( TaintedPopper as any, {
			global: { plugins: [ store, [ Message, { messageToTextFunction } ], trackPlugin ] },
			props: { guid: 'a-guid' },
		} );
		expect( wrapper.find( '.wb-tr-popper__text--top' ).element.textContent )
			.toMatch( '(wikibase-tainted-ref-popper-text)' );
		expect( wrapper.find( '.wb-tr-popper-title' ).element.textContent )
			.toMatch( '(wikibase-tainted-ref-popper-title)' );
		expect( ( wrapper.find( '.wb-tr-popper-help a' ).element as HTMLElement ).title )
			.toMatch( '(wikibase-tainted-ref-popper-help-link-title)' );
		expect( wrapper.find( '.wb-tr-popper-help' ).element.textContent )
			.toMatch( '(wikibase-tainted-ref-popper-help-link-text)' );
		expect( wrapper.find( '.wb-tr-popper-remove-warning' ).element.textContent )
			.toMatch( '(wikibase-tainted-ref-popper-remove-warning)' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-text' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-title' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-help-link-title' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-help-link-text' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-remove-warning' );
	} );
} );
