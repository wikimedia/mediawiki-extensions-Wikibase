import { createLocalVue, mount } from '@vue/test-utils';
import Vuex, { Store } from 'vuex';
import Track from '@/vue-plugins/Track';
import Message from '@/vue-plugins/Message';
import Application from '@/store/Application';
import { createStore } from '@/store';
import TaintedPopper from '@/presentation/components/TaintedPopper.vue';
import { POPPER_HIDE, HELP_LINK_SET, STATEMENT_TAINTED_STATE_UNTAINT } from '@/store/actionTypes';

const localVue = createLocalVue();
const trackingFunction: any = jest.fn();
localVue.use( Vuex );
localVue.use( Message, { messageToTextFunction: () => {
	return 'dummy';
} } );
localVue.use( Track, { trackingFunction } );

describe( 'TaintedPopper.vue', () => {
	it( 'sets the help link according to the store', () => {
		const store: Store<Application> = createStore();
		const wrapper = mount( TaintedPopper, {
			store,
			localVue,
		} );
		store.dispatch( HELP_LINK_SET, 'https://wdtest/Help' );
		expect( wrapper.find( '.wb-tr-popper-help a' ).attributes().href ).toEqual( 'https://wdtest/Help' );
	} );
	it( 'clicking the help link triggers a tracking event', () => {
		const store: Store<Application> = createStore();
		const wrapper = mount( TaintedPopper, {
			store,
			localVue,
		} );
		store.dispatch( HELP_LINK_SET, 'https://wdtest/Help' );
		wrapper.find( '.wb-tr-popper-help a' ).trigger( 'click' );
		expect( trackingFunction ).toHaveBeenCalledWith( 'counter.wikibase.view.tainted-ref.helpLinkClick', 1 );
	} );
	it( 'does not close the popper when the help link is focused', () => {
		const store: Store<Application> = createStore();
		store.dispatch = jest.fn();

		const wrapper = mount( TaintedPopper, {
			store,
			localVue,
			propsData: { guid: 'a-guid' },
		} );
		wrapper.trigger(
			'focusout', {
				relatedTarget: wrapper.find( '.wb-tr-popper-help' ).element,
			},
		);
		expect( store.dispatch ).not.toHaveBeenCalledWith( POPPER_HIDE, 'a-guid' );
	} );
	it( 'clicking the remove warning button untaints the statements', () => {
		const store: Store<Application> = createStore();
		store.dispatch = jest.fn();

		const wrapper = mount( TaintedPopper, {
			store,
			localVue,
			propsData: { guid: 'a-guid' },
		} );
		wrapper.find( '.wb-tr-popper-remove-warning' ).trigger( 'click' );
		expect( store.dispatch ).toHaveBeenCalledWith( STATEMENT_TAINTED_STATE_UNTAINT, 'a-guid' );
	} );
	it( 'clicking the remove warning button triggers a tracking event', () => {
		const store: Store<Application> = createStore();
		const wrapper = mount( TaintedPopper, {
			store,
			localVue,
		} );
		wrapper.find( '.wb-tr-popper-remove-warning' ).trigger( 'click' );
		expect( trackingFunction ).toHaveBeenCalledWith( 'counter.wikibase.view.tainted-ref.removeWarningClick', 1 );
	} );
	it( 'popper texts are taken from our Vue message plugin', () => {
		const localVue = createLocalVue();
		const messageToTextFunction = jest.fn();
		messageToTextFunction.mockImplementation( ( key ) => `(${key})` );

		localVue.use( Vuex );
		localVue.use( Message, { messageToTextFunction } );

		const store: Store<Application> = createStore();
		store.dispatch = jest.fn();

		const wrapper = mount( TaintedPopper, {
			store,
			localVue,
			propsData: { guid: 'a-guid' },
		} );
		expect( wrapper.find( '.wb-tr-popper__text--top' ).element.textContent )
			.toMatch( '(wikibase-tainted-ref-popper-text)' );
		expect( wrapper.find( '.wb-tr-popper-title' ).element.textContent )
			.toMatch( '(wikibase-tainted-ref-popper-title)' );
		expect( wrapper.find( '.wb-tr-popper-help a' ).element.title )
			.toMatch( '(wikibase-tainted-ref-popper-help-link-title)' );
		expect( wrapper.find( '.wb-tr-popper-help' ).element.textContent )
			.toMatch( '(wikibase-tainted-ref-popper-help-link-text)' );
		expect( wrapper.find( '.wb-tr-popper-feedback' ).element.textContent )
			.toMatch( '(wikibase-tainted-ref-popper-feedback-text)' );
		expect( wrapper.find( '.wb-tr-popper-feedback a' ).element.title )
			.toMatch( '(wikibase-tainted-ref-popper-feedback-link-title)' );
		expect( wrapper.find( '.wb-tr-popper-feedback a' ).element.textContent )
			.toMatch( '(wikibase-tainted-ref-popper-feedback-link-text)' );
		expect( wrapper.find( '.wb-tr-popper-remove-warning' ).element.textContent )
			.toMatch( '(wikibase-tainted-ref-popper-remove-warning)' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-text' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-title' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-help-link-title' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-help-link-text' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-feedback-text' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-feedback-link-text' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-feedback-link-title' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-remove-warning' );
	} );
} );
