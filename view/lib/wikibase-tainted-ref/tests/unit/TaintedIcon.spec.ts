import { createLocalVue, shallowMount } from '@vue/test-utils';
import Vuex, { Store } from 'vuex';
import Application from '@/store/Application';
import { createStore } from '@/store';
import TaintedIcon from '@/presentation/components/TaintedIcon.vue';
import { POPPER_SHOW } from '@/store/actionTypes';
import Track from '@/vue-plugins/Track';
import Message from '@/vue-plugins/Message';

const localVue = createLocalVue();
localVue.use( Vuex );
localVue.use( Track, { trackingFunction: () => {
	// do nothing on track
} } );
localVue.use( Message, { messageToTextFunction: () => {
	return 'dummy';
} } );

describe( 'TaintedIcon.vue', () => {
	it( 'should render the icon', () => {
		const store: Store<Application> = createStore();
		const wrapper = shallowMount( TaintedIcon, {
			store,
			localVue,
		} );
		expect( wrapper.element.tagName ).toEqual( 'A' );
		expect( wrapper.classes() ).toContain( 'wb-tr-tainted-icon' );
	} );
	it( 'opens the popper on click', () => {
		const store: Store<Application> = createStore();
		store.dispatch = jest.fn();

		const wrapper = shallowMount( TaintedIcon, {
			store,
			localVue,
			propsData: { guid: 'a-guid' },
		} );
		wrapper.trigger( 'click' );
		expect( store.dispatch ).toHaveBeenCalledWith( POPPER_SHOW, 'a-guid' );
	} );
	it( 'is an un-clickable div if the popper is open', () => {
		const store: Store<Application> = createStore();
		store.dispatch( POPPER_SHOW, 'a-guid' );

		store.dispatch = jest.fn();
		const wrapper = shallowMount( TaintedIcon, {
			store,
			localVue,
			propsData: { guid: 'a-guid' },
		} );

		expect( wrapper.element.tagName ).toEqual( 'DIV' );
		expect( wrapper.classes() ).toContain( 'wb-tr-tainted-icon' );

		wrapper.trigger( 'click' );
		expect( store.dispatch ).not.toHaveBeenCalledWith( POPPER_SHOW, 'a-guid' );
	} );
	it( 'clicking the tainted icon triggers a tracking event', () => {
		const trackingFunction = jest.fn();
		const localVue = createLocalVue();
		localVue.use( Vuex );
		localVue.use( Track, { trackingFunction } );
		localVue.use( Message, { messageToTextFunction: () => {
			return 'dummy';
		} } );

		const store: Store<Application> = createStore();
		const wrapper = shallowMount( TaintedIcon, {
			store,
			localVue,
		} );
		wrapper.trigger( 'click' );
		expect( trackingFunction ).toHaveBeenCalledWith( 'counter.wikibase.view.tainted-ref.taintedIconClick', 1 );
	} );
	it( 'uses the title text from the message plugin', () => {
		const localVue = createLocalVue();
		const messageToTextFunction = jest.fn();
		messageToTextFunction.mockReturnValue( 'DUMMY_TEXT' );

		localVue.use( Vuex );
		localVue.use( Message, { messageToTextFunction } );

		const store: Store<Application> = createStore();
		const wrapper = shallowMount( TaintedIcon, {
			store,
			localVue,
		} );
		expect( wrapper.find( '.wb-tr-tainted-icon' ).element.title ).toMatch( 'DUMMY_TEXT' );
		expect( messageToTextFunction ).toHaveBeenCalledWith( 'wikibase-tainted-ref-tainted-icon-title' );
	} );
} );
