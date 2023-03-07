import {
	shallowMount,
} from '@vue/test-utils';
import MessageKeys from '@/definitions/MessageKeys';
import ErrorUnknown from '@/presentation/components/ErrorUnknown.vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import ReportIssue from '@/presentation/components/ReportIssue.vue';
import { createTestStore } from '../../../util/store';
import { ComponentOptions } from 'vue';

describe( 'ErrorUnknown', () => {
	const $messages = {
		KEYS: MessageKeys,
		get: jest.fn( ( key: string ) => `⧼${key}⧽` ),
		getText: jest.fn( ( key: string ) => `⧼${key}⧽` ),
	};
	const mocks = { $messages };

	const trackErrorsFallingBackToGenericView = jest.fn();

	const store = createTestStore( { actions: { trackErrorsFallingBackToGenericView } } );

	it( 'creates a heading with the right message', () => {
		const wrapper = shallowMount( ErrorUnknown, { global: { mocks, plugins: [ store ] } } );
		const heading = wrapper.find( 'h2' );
		expect( heading.exists() ).toBe( true );
		expect( heading.text() ).toBe( `⧼${MessageKeys.UNKNOWN_ERROR_HEADING}⧽` );
	} );

	it( 'mounts an IconMessageBox with the right message', () => {
		const wrapper = shallowMount(
			ErrorUnknown,
			{ global: { mocks, plugins: [ store ], stubs: { IconMessageBox } } },
		);
		const iconMessageBox = wrapper.findComponent( IconMessageBox );
		expect( iconMessageBox.exists() ).toBe( true );
		expect( iconMessageBox.text() ).toBe( `⧼${MessageKeys.UNKNOWN_ERROR_MESSAGE}⧽` );
	} );

	it( 'mounts a ReportIssue', () => {
		const wrapper = shallowMount( ErrorUnknown, { global: { mocks, plugins: [ store ] } } );
		expect( wrapper.findComponent( ReportIssue ).exists() ).toBe( true );
	} );

	it( 'mounts an EventEmittingButton with the right props', () => {
		const wrapper = shallowMount( ErrorUnknown, { global: { mocks, plugins: [ store ] } } );
		const eventEmittingButton = wrapper.findComponent( EventEmittingButton );
		expect( eventEmittingButton.exists() ).toBe( true );
		expect( eventEmittingButton.props( 'type' ) ).toBe( 'primaryProgressive' );
		expect( eventEmittingButton.props( 'size' ) ).toBe( 'M' );
		expect( eventEmittingButton.props( 'message' ) ).toBe( `⧼${MessageKeys.ERROR_RELOAD_BRIDGE}⧽` );
	} );

	it( 'dispatches trackErrorsFallingBackToGenericView on mount', () => {
		shallowMount( ErrorUnknown, { global: { mocks, plugins: [ store ] } } );
		expect( trackErrorsFallingBackToGenericView ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'repeats relaunch button\'s "click" event as "relaunch"', () => {
		const wrapper = shallowMount( ErrorUnknown, { global: { plugins: [ store ] } } );
		wrapper.findComponent<ComponentOptions>( '.wb-db-error-unknown__relaunch' ).vm.$emit( 'click' );
		expect( wrapper.emitted( 'relaunch' ) ).toHaveLength( 1 );
	} );
} );
