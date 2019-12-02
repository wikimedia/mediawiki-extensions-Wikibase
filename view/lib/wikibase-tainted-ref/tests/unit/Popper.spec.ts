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
	it( 'popper text is taken from our Vue message plugin', () => {
		const localVue = createLocalVue();
		const messageToTextFunction = jest.fn();
		messageToTextFunction.mockReturnValue( 'DUMMY_TEXT' );

		localVue.use( Vuex );
		localVue.use( Message, { messageToTextFunction } );

		const store: Store<Application> = createStore();
		store.dispatch = jest.fn();

		const wrapper = shallowMount( Popper, {
			store,
			localVue,
			propsData: { guid: 'a-guid' },
		} );
		expect( wrapper.find( '.wb-tr-popper-text' ).element.textContent ).toMatch( 'DUMMY_TEXT' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-popper-text' );
	} );
} );
