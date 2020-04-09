import { createLocalVue, shallowMount } from '@vue/test-utils';
import MessageKeys from '@/definitions/MessageKeys';
import ProcessDialogHeader from '@/presentation/components/ProcessDialogHeader.vue';
import { calledWithHTMLElement } from '../../../util/assertions';
import AppHeader from '@/presentation/components/AppHeader.vue';
import Vuex, { Store } from 'vuex';
import Application from '@/store/Application';
import { createStore } from '@/store';
import newMockServiceContainer from '../../services/newMockServiceContainer';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import { initEvents } from '@/events';
import { ErrorTypes } from '@/definitions/ApplicationError';

const localVue = createLocalVue();
localVue.use( Vuex );

describe( 'AppHeader', () => {
	let store: Store<Application>;

	beforeEach( () => {
		store = createStore( newMockServiceContainer( {} ) );
	} );

	it( 'shows the header with title', () => {
		const propertyId = 'P123';
		const titleMessage = 'he ho';
		store.commit( 'setPropertyPointer', propertyId );
		const messageGet = jest.fn().mockReturnValue( titleMessage );
		const wrapper = shallowMount( AppHeader, {
			store,
			localVue,
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
			},
			stubs: { ProcessDialogHeader },
		} );

		calledWithHTMLElement( messageGet, 1, 1 );

		expect( wrapper.find( ProcessDialogHeader ).exists() ).toBe( true );
		expect( messageGet ).toHaveBeenCalledWith(
			MessageKeys.BRIDGE_DIALOG_TITLE,
			`<span class="wb-db-term-label" lang="zxx" dir="auto">${propertyId}</span>`,
		);
		expect( wrapper.find( 'h1' ).text() ).toBe( titleMessage );
	} );

	describe( 'save button rendering', () => {
		it( 'renders the save button using the SAVE_CHANGES message', () => {
			const saveMessage = 'go go go';
			const messageGet = jest.fn(
				( key: string ) => {
					if ( key === MessageKeys.SAVE_CHANGES ) {
						return saveMessage;
					}

					return '';
				},
			);

			const wrapper = shallowMount( AppHeader, {
				store,
				localVue,
				mocks: {
					$bridgeConfig: { usePublish: false },
					$messages: {
						KEYS: MessageKeys,
						get: messageGet,
					},
				},
				stubs: { ProcessDialogHeader, EventEmittingButton },
			} );

			expect( messageGet ).toHaveBeenCalledWith( MessageKeys.SAVE_CHANGES );
			const button = wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' );
			expect( button.props( 'message' ) ).toBe( saveMessage );
		} );

		it( 'renders the save button using the PUBLISH_CHANGES message', () => {
			const publishMessage = 'run run run';
			const messageGet = jest.fn(
				( key: string ) => {
					if ( key === MessageKeys.PUBLISH_CHANGES ) {
						return publishMessage;
					}

					return '';
				},
			);

			const wrapper = shallowMount( AppHeader, {
				store,
				localVue,
				mocks: {
					$bridgeConfig: { usePublish: true },
					$messages: {
						KEYS: MessageKeys,
						get: messageGet,
					},
				},
				stubs: { ProcessDialogHeader, EventEmittingButton },
			} );

			expect( messageGet ).toHaveBeenCalledWith( MessageKeys.PUBLISH_CHANGES );
			const button = wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' );
			expect( button.props( 'message' ) ).toBe( publishMessage );
		} );
	} );

	it( 'disables the save button while saving', async () => {
		const wrapper = shallowMount( AppHeader, {
			store,
			localVue,
			stubs: { ProcessDialogHeader, EventEmittingButton },
		} );

		store.commit( 'setApplicationStatus', ApplicationStatus.SAVING );
		await wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' ).trigger( 'click' );
		await localVue.nextTick();

		expect( wrapper.emitted( initEvents.saved ) ).toBeFalsy();
	} );

	it( 'hides the save button after changes are saved', async () => {
		const wrapper = shallowMount( AppHeader, {
			store,
			localVue,
			stubs: { ProcessDialogHeader, EventEmittingButton },
		} );

		store.commit( 'setApplicationStatus', ApplicationStatus.SAVED );
		expect( wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' ).exists() ).toBe( false );
	} );

	it( 'renders the close button using the CANCEL message', () => {
		const cancelMessage = 'cancel that';
		const messageGet = jest.fn().mockReturnValue( cancelMessage );
		const wrapper = shallowMount( AppHeader, {
			store,
			localVue,
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
			},
			stubs: { ProcessDialogHeader, EventEmittingButton },
		} );

		expect( messageGet ).toHaveBeenCalledWith( MessageKeys.CANCEL );
		const button = wrapper.find( '.wb-ui-event-emitting-button--close' );
		expect( button.props( 'message' ) ).toBe( cancelMessage );
	} );

	it( 'bubbles the click event from the save button as save event', async () => {
		const wrapper = shallowMount( AppHeader, {
			store,
			localVue,
			stubs: { ProcessDialogHeader, EventEmittingButton },
		} );

		await wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' ).vm.$emit( 'click' );
		await localVue.nextTick();

		expect( wrapper.emitted( 'save' ) ).toHaveLength( 1 );
	} );

	it( 'bubbles the click event from the close button as close event', async () => {
		const wrapper = shallowMount( AppHeader, {
			store,
			localVue,
			stubs: { ProcessDialogHeader, EventEmittingButton },
		} );

		await wrapper.find( '.wb-ui-event-emitting-button--close' ).vm.$emit( 'click' );
		await localVue.nextTick();

		expect( wrapper.emitted( 'close' ) ).toHaveLength( 1 );
	} );

	it( 'disables close while in saving state', async () => {
		store.commit( 'setApplicationStatus', ApplicationStatus.SAVING );
		const wrapper = shallowMount( AppHeader, {
			store,
			localVue,
			stubs: { ProcessDialogHeader, EventEmittingButton },
		} );

		await wrapper.find( '.wb-ui-event-emitting-button--close' ).trigger( 'click' );
		await localVue.nextTick();

		expect( wrapper.emitted( initEvents.cancel ) ).toBeFalsy();
	} );

	describe( 'if there is an error', () => {
		it( 'doesn\'t show the save button ', () => {
			store.commit( 'addApplicationErrors', [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ] );
			const wrapper = shallowMount( AppHeader, {
				store,
				localVue,
				stubs: { ProcessDialogHeader, EventEmittingButton },
			} );

			expect( wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' ).exists() ).toBe( false );
		} );
	} );

} );
