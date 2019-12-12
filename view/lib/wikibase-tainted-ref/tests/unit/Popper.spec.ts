import { createLocalVue, shallowMount } from '@vue/test-utils';
import Vuex, { Store } from 'vuex';
import Track from '@/vue-plugins/Track';
import Message from '@/vue-plugins/Message';
import Application from '@/store/Application';
import { createStore } from '@/store';
import Popper from '@/presentation/components/Popper.vue';
import { POPPER_HIDE, HELP_LINK_SET } from '@/store/actionTypes';

const localVue = createLocalVue();
localVue.use( Vuex );
localVue.use( Message, { messageToTextFunction: () => {
	return 'dummy';
} } );

describe( 'Popper.vue', () => {
	it( 'should render the Popper', () => {
		const store: Store<Application> = createStore();
		const wrapper = shallowMount( Popper, {
			store,
			localVue,
		} );
		expect( wrapper.classes() ).toContain( 'wb-tr-popper-wrapper' );
	} );
	it( 'sets the help link according to the store', () => {
		const store: Store<Application> = createStore();
		const wrapper = shallowMount( Popper, {
			store,
			localVue,
		} );
		store.dispatch( HELP_LINK_SET, 'https://wdtest/Help' );
		expect( wrapper.find( '.wb-tr-popper-help' ).attributes().href ).toEqual( 'https://wdtest/Help' );
	} );
	it( 'clicking the help link triggers a tracking event', () => {
		const trackingFunction = jest.fn();
		localVue.use( Track, { trackingFunction } );
		const store: Store<Application> = createStore();
		const wrapper = shallowMount( Popper, {
			store,
			localVue,
		} );
		store.dispatch( HELP_LINK_SET, 'https://wdtest/Help' );
		wrapper.find( '.wb-tr-popper-help' ).trigger( 'click' );
		expect( trackingFunction ).toHaveBeenCalledWith( 'counter.wikibase.view.tainted-ref.helpLinkClick', 1 );
	} );
	it( 'closes the popper when the x is clicked', () => {
		const store: Store<Application> = createStore();
		store.dispatch = jest.fn();

		const wrapper = shallowMount( Popper, {
			store,
			localVue,
			propsData: { guid: 'a-guid' },
		} );
		wrapper.find( '.wb-tr-popper-close' ).trigger( 'click' );
		expect( store.dispatch ).toHaveBeenCalledWith( POPPER_HIDE, 'a-guid' );
	} );
	it( 'closes the popper when the focus is lost', () => {
		const store: Store<Application> = createStore();
		store.dispatch = jest.fn();

		const wrapper = shallowMount( Popper, {
			store,
			localVue,
			propsData: { guid: 'a-guid' },
		} );
		wrapper.trigger( 'focusout' );
		expect( store.dispatch ).toHaveBeenCalledWith( POPPER_HIDE, 'a-guid' );
	} );
	it( 'does not close the popper when the help link is focused', () => {
		const store: Store<Application> = createStore();
		store.dispatch = jest.fn();

		const wrapper = shallowMount( Popper, {
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
	it( 'popper texts are taken from our Vue message plugin', () => {
		const localVue = createLocalVue();
		const messageToTextFunction = jest.fn();
		messageToTextFunction.mockImplementation( ( key ) => `(${key})` );

		localVue.use( Vuex );
		localVue.use( Message, { messageToTextFunction } );

		const store: Store<Application> = createStore();
		store.dispatch = jest.fn();

		const wrapper = shallowMount( Popper, {
			store,
			localVue,
			propsData: { guid: 'a-guid' },
		} );
		expect( wrapper.find( '.wb-tr-popper-text' ).element.textContent )
			.toMatch( '(wikibase-tainted-ref-popper-text)' );
		expect( wrapper.find( '.wb-tr-popper-title' ).element.textContent )
			.toMatch( '(wikibase-tainted-ref-popper-title)' );
		expect( wrapper.find( '.wb-tr-popper-help' ).element.title )
			.toMatch( '(wikibase-tainted-ref-popper-help-link-title)' );
		expect( wrapper.find( '.wb-tr-popper-help' ).element.textContent )
			.toMatch( '(wikibase-tainted-ref-popper-help-link-text)' );
		expect( wrapper.find( '.wb-tr-popper-feedback' ).element.textContent )
			.toMatch( '(wikibase-tainted-ref-popper-feedback-text)' );
		expect( wrapper.find( '.wb-tr-popper-feedback a' ).element.title )
			.toMatch( '(wikibase-tainted-ref-popper-feedback-link-title)' );
		expect( wrapper.find( '.wb-tr-popper-feedback a' ).element.textContent )
			.toMatch( '(wikibase-tainted-ref-popper-feedback-link-text)' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-text' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-title' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-help-link-title' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-help-link-text' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-feedback-text' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-feedback-link-text' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-feedback-link-title' );
	} );
} );
