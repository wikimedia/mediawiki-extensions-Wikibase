import Vuex from 'vuex';
import {
	createLocalVue,
	shallowMount,
} from '@vue/test-utils';
import MessageKeys from '@/definitions/MessageKeys';
import ErrorUnknown from '@/presentation/components/ErrorUnknown.vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import ReportIssue from '@/presentation/components/ReportIssue.vue';
import { createTestStore } from '../../../util/store';

describe( 'ErrorUnknown', () => {
	const $messages = {
		KEYS: MessageKeys,
		get: jest.fn( ( key: string ) => `⧼${key}⧽` ),
	};
	const mocks = { $messages };

	const trackErrorsFallingBackToGenericView = jest.fn();
	const store = createTestStore( { actions: { trackErrorsFallingBackToGenericView } } );

	const localVue = createLocalVue();
	localVue.use( Vuex );

	it( 'creates a heading with the right message', () => {
		const wrapper = shallowMount( ErrorUnknown, { mocks, store, localVue } );
		const heading = wrapper.find( 'h2' );
		expect( heading.exists() ).toBe( true );
		expect( heading.text() ).toBe( `⧼${MessageKeys.UNKNOWN_ERROR_HEADING}⧽` );
	} );

	it( 'mounts an IconMessageBox with the right message', () => {
		const wrapper = shallowMount( ErrorUnknown, { mocks, store, localVue } );
		const iconMessageBox = wrapper.find( IconMessageBox );
		expect( iconMessageBox.exists() ).toBe( true );
		expect( iconMessageBox.text() ).toBe( `⧼${MessageKeys.UNKNOWN_ERROR_MESSAGE}⧽` );
	} );

	it( 'mounts a ReportIssue', () => {
		const wrapper = shallowMount( ErrorUnknown, { mocks, store, localVue } );
		expect( wrapper.find( ReportIssue ).exists() ).toBe( true );
	} );

	it( 'mounts an EventEmittingButton with the right props', () => {
		const wrapper = shallowMount( ErrorUnknown, { mocks, store, localVue } );
		const eventEmittingButton = wrapper.find( EventEmittingButton );
		expect( eventEmittingButton.exists() ).toBe( true );
		expect( eventEmittingButton.props( 'type' ) ).toBe( 'primaryProgressive' );
		expect( eventEmittingButton.props( 'size' ) ).toBe( 'M' );
		expect( eventEmittingButton.props( 'message' ) ).toBe( `⧼${MessageKeys.ERROR_RELOAD_BRIDGE}⧽` );
	} );

	it( 'dispatches trackErrorsFallingBackToGenericView on mount', () => {
		shallowMount( ErrorUnknown, { mocks, store, localVue } );
		expect( trackErrorsFallingBackToGenericView ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'repeats relaunch button\'s "click" event as "relaunch"', () => {
		const wrapper = shallowMount( ErrorUnknown, { store } );
		wrapper.find( '.wb-db-error-unknown__relaunch' ).vm.$emit( 'click' );
		expect( wrapper.emitted( 'relaunch' ) ).toHaveLength( 1 );
	} );
} );
