import { shallowMount } from '@vue/test-utils';
import MessageKeys from '@/definitions/MessageKeys';
import ErrorUnknown from '@/presentation/components/ErrorUnknown.vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import ReportIssue from '@/presentation/components/ReportIssue.vue';

describe( 'ErrorUnknown', () => {
	const $messages = {
		KEYS: MessageKeys,
		get: jest.fn( ( key: string ) => `⧼${key}⧽` ),
	};

	it( 'creates a heading with the right message', () => {
		const wrapper = shallowMount( ErrorUnknown, { mocks: { $messages } } );
		const heading = wrapper.find( 'h2' );
		expect( heading.exists() ).toBe( true );
		expect( heading.text() ).toBe( `⧼${MessageKeys.UNKNOWN_ERROR_HEADING}⧽` );
	} );

	it( 'mounts an IconMessageBox with the right message', () => {
		const wrapper = shallowMount( ErrorUnknown, { mocks: { $messages } } );
		const iconMessageBox = wrapper.find( IconMessageBox );
		expect( iconMessageBox.exists() ).toBe( true );
		expect( iconMessageBox.text() ).toBe( `⧼${MessageKeys.UNKNOWN_ERROR_MESSAGE}⧽` );
	} );

	it( 'mounts a ReportIssue', () => {
		const wrapper = shallowMount( ErrorUnknown, { mocks: { $messages } } );
		expect( wrapper.find( ReportIssue ).exists() ).toBe( true );
	} );

	it( 'mounts an EventEmittingButton with the right props', () => {
		const wrapper = shallowMount( ErrorUnknown, { mocks: { $messages } } );
		const eventEmittingButton = wrapper.find( EventEmittingButton );
		expect( eventEmittingButton.exists() ).toBe( true );
		expect( eventEmittingButton.props( 'type' ) ).toBe( 'primaryProgressive' );
		expect( eventEmittingButton.props( 'size' ) ).toBe( 'M' );
		expect( eventEmittingButton.props( 'message' ) ).toBe( `⧼${MessageKeys.ERROR_RELOAD_BRIDGE}⧽` );
	} );
} );
