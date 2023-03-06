import {
	config,
	shallowMount,
} from '@vue/test-utils';
import { createTestStore } from '../../../util/store';
import MessageKeys from '@/definitions/MessageKeys';
import ErrorSaving from '@/presentation/components/ErrorSaving.vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import ReportIssue from '@/presentation/components/ReportIssue.vue';

beforeAll( () => {
	config.global.renderStubDefaultSlot = true;
} );

afterAll( () => {
	config.global.renderStubDefaultSlot = false;
} );

describe( 'ErrorSaving', () => {
	const $messages = {
		KEYS: MessageKeys,
		get: jest.fn( ( key: string ) => `⧼${key}⧽` ),
		getText: jest.fn( ( key: string ) => `⧼${key}⧽` ),
	};
	const mocks = { $messages };

	const retrySave = jest.fn();
	const trackSavingErrorsFallingBackToGenericView = jest.fn();

	const store = createTestStore( { actions: { retrySave, trackSavingErrorsFallingBackToGenericView } } );

	it( 'creates a heading with the right message', () => {
		const wrapper = shallowMount( ErrorSaving, { global: { mocks, plugins: [ store ] } } );
		const heading = wrapper.find( 'h2' );
		expect( heading.exists() ).toBe( true );
		expect( heading.text() ).toBe( `⧼${MessageKeys.SAVING_ERROR_HEADING}⧽` );
	} );

	it( 'mounts an IconMessageBox with the right message', () => {
		const wrapper = shallowMount( ErrorSaving, { global: { mocks, plugins: [ store ] } } );
		const iconMessageBox = wrapper.findComponent( IconMessageBox );
		expect( iconMessageBox.exists() ).toBe( true );
		expect( iconMessageBox.text() ).toBe( `⧼${MessageKeys.SAVING_ERROR_MESSAGE}⧽` );
	} );

	it( 'mounts a ReportIssue', () => {
		const wrapper = shallowMount( ErrorSaving, { global: { mocks, plugins: [ store ] } } );
		expect( wrapper.findComponent( ReportIssue ).exists() ).toBe( true );
	} );

	it( 'mounts first EventEmittingButton with the right props', () => {
		const wrapper = shallowMount( ErrorSaving, { global: { mocks, plugins: [ store ] } } );
		const eventEmittingButton = wrapper.findAllComponents( EventEmittingButton )[ 0 ];
		expect( eventEmittingButton.exists() ).toBe( true );
		expect( eventEmittingButton.props( 'type' ) ).toBe( 'neutral' );
		expect( eventEmittingButton.props( 'size' ) ).toBe( 'M' );
		expect( eventEmittingButton.props( 'message' ) ).toBe( `⧼${MessageKeys.ERROR_GO_BACK}⧽` );
	} );

	it( 'mounts second EventEmittingButton with the right props', () => {
		const wrapper = shallowMount( ErrorSaving, { global: { mocks, plugins: [ store ] } } );
		const eventEmittingButton = wrapper.findAllComponents( EventEmittingButton )[ 1 ];
		expect( eventEmittingButton.exists() ).toBe( true );
		expect( eventEmittingButton.props( 'type' ) ).toBe( 'primaryProgressive' );
		expect( eventEmittingButton.props( 'size' ) ).toBe( 'M' );
		expect( eventEmittingButton.props( 'message' ) ).toBe( `⧼${MessageKeys.ERROR_RETRY_SAVE}⧽` );
	} );

	it( 'includes no whitespace between the two buttons', () => {
		const wrapper = shallowMount( ErrorSaving, { global: { mocks, plugins: [ store ] } } );
		const buttons = wrapper.find( '.wb-db-error-saving__buttons' );
		expect( buttons.element.textContent ).toBe( '' );
	} );

	it( 'dispatches retrySave action when the retry save button is clicked', async () => {
		const wrapper = shallowMount( ErrorSaving, { global: { mocks, plugins: [ store ] } } );
		const button = wrapper.find( '.wb-db-error-saving__buttons .wb-db-error-saving__retry' );
		await button.trigger( 'click' );

		expect( retrySave ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'goes back if the back button is clicked', async () => {
		const goBackFromErrorToReady = jest.fn();
		const localStore = createTestStore( { actions: {
			goBackFromErrorToReady,
			trackSavingErrorsFallingBackToGenericView,
		} } );
		const wrapper = shallowMount( ErrorSaving, {
			global: { plugins: [ localStore ] },
		} );

		const button = wrapper.find( '.wb-db-error-saving__buttons .wb-db-error-saving__back' );
		expect( button.exists() ).toBe( true );
		await button.trigger( 'click' );

		expect( goBackFromErrorToReady ).toHaveBeenCalled();
	} );

	it( 'dispatches trackSavingErrorsFallingBackToGenericView on mount', () => {
		shallowMount( ErrorSaving, { global: { mocks, plugins: [ store ] } } );
		expect( trackSavingErrorsFallingBackToGenericView ).toHaveBeenCalledTimes( 1 );
	} );
} );
