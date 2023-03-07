import { shallowMount } from '@vue/test-utils';
import MessageKeys from '@/definitions/MessageKeys';
import ProcessDialogHeader from '@/presentation/components/ProcessDialogHeader.vue';
import TermLabel from '@/presentation/components/TermLabel.vue';
import { calledWithHTMLElement } from '../../../util/assertions';
import AppHeader from '@/presentation/components/AppHeader.vue';
import Application from '@/store/Application';
import { createStore } from '@/store';
import newMockServiceContainer from '../../services/newMockServiceContainer';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import { initEvents } from '@/events';
import { ErrorTypes } from '@/definitions/ApplicationError';
import { ComponentOptions, nextTick } from 'vue';
import newMockTracker from '../../../util/newMockTracker';
import { MutableStore } from '../../../util/store';

describe( 'AppHeader', () => {
	let store: MutableStore<Application>;

	beforeEach( () => {
		store = createStore( newMockServiceContainer( { tracker: newMockTracker() } ) );
		store.commit( 'setClientConfig', { usePublish: false } );
	} );

	it( 'shows the header with title', () => {
		const propertyId = 'P123';
		const titleMessage = 'he ho';
		store.commit( 'setPropertyPointer', propertyId );
		const messageGet = jest.fn().mockReturnValue( titleMessage );
		const wrapper = shallowMount( AppHeader, {
			global: {
				plugins: [ store ],
				mocks: {
					$messages: {
						KEYS: MessageKeys,
						get: messageGet,
						getText: messageGet,
					},
				},
				stubs: { ProcessDialogHeader, TermLabel },
			},
		} );

		calledWithHTMLElement( messageGet, 1, 1 );

		expect( wrapper.findComponent( ProcessDialogHeader ).exists() ).toBe( true );
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
				global: {
					plugins: [ store ],
					mocks: {
						$messages: {
							KEYS: MessageKeys,
							get: messageGet,
							getText: messageGet,
						},
					},
					stubs: { ProcessDialogHeader, EventEmittingButton },
				},
			} );

			expect( messageGet ).toHaveBeenCalledWith( MessageKeys.SAVE_CHANGES );
			const button = wrapper.findComponent<ComponentOptions>(
				'.wb-ui-event-emitting-button--primaryProgressive',
			);
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

			store.commit( 'setClientConfig', { usePublish: true } );
			const wrapper = shallowMount( AppHeader, {
				global: {
					plugins: [ store ],
					mocks: {
						$messages: {
							KEYS: MessageKeys,
							get: messageGet,
							getText: messageGet,
						},
					},
					stubs: { ProcessDialogHeader, EventEmittingButton },
				},
			} );

			expect( messageGet ).toHaveBeenCalledWith( MessageKeys.PUBLISH_CHANGES );
			const button = wrapper.findComponent<ComponentOptions>(
				'.wb-ui-event-emitting-button--primaryProgressive',
			);
			expect( button.props( 'message' ) ).toBe( publishMessage );
		} );

		it( 'disables the save button while saving', async () => {
			const wrapper = shallowMount( AppHeader, {
				global: {
					plugins: [ store ],
					stubs: { ProcessDialogHeader, EventEmittingButton },
				},
			} );

			store.commit( 'setApplicationStatus', ApplicationStatus.SAVING );
			await wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' ).trigger( 'click' );

			expect( wrapper.emitted( initEvents.saved ) ).toBeFalsy();
		} );

		it( 'hides the save button after changes are saved', async () => {
			const wrapper = shallowMount( AppHeader, {
				global: {
					plugins: [ store ],
					stubs: { ProcessDialogHeader, EventEmittingButton },
				},
			} );

			store.commit( 'setApplicationStatus', ApplicationStatus.SAVED );
			await nextTick();

			expect( wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' ).exists() ).toBe( false );
		} );

		it( 'doesn\'t show the save button if there is an error', () => {
			store.commit( 'addApplicationErrors', [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ] );
			const wrapper = shallowMount( AppHeader, {
				global: {
					plugins: [ store ],
					stubs: { ProcessDialogHeader, EventEmittingButton },
				},
			} );

			expect( wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' ).exists() ).toBe( false );
		} );

		it( 'hides the save button while warning about anonymous editing', () => {
			store.commit( 'setShowWarningAnonymousEdit', true );
			const wrapper = shallowMount( AppHeader, {
				global: {
					plugins: [ store ],
					stubs: { ProcessDialogHeader, EventEmittingButton },
				},
			} );

			expect( wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' ).exists() ).toBe( false );
		} );
	} );

	describe( 'close button rendering', () => {
		it( 'renders the close button using the CANCEL message', () => {
			const cancelMessage = 'cancel that';
			const messageGet = jest.fn().mockReturnValue( cancelMessage );
			const wrapper = shallowMount( AppHeader, {
				global: {
					plugins: [ store ],
					mocks: {
						$messages: {
							KEYS: MessageKeys,
							get: messageGet,
							getText: messageGet,
						},
					},
					stubs: { ProcessDialogHeader, EventEmittingButton },
				},
			} );

			expect( messageGet ).toHaveBeenCalledWith( MessageKeys.CANCEL );
			const button = wrapper.findComponent<ComponentOptions>( '.wb-ui-event-emitting-button--close' );
			expect( button.props( 'message' ) ).toBe( cancelMessage );
		} );

		it( 'disables close while in saving state', async () => {
			store.commit( 'setApplicationStatus', ApplicationStatus.SAVING );
			const wrapper = shallowMount( AppHeader, {
				global: {
					plugins: [ store ],
					stubs: { ProcessDialogHeader, EventEmittingButton },
				},
			} );

			await wrapper.find( '.wb-ui-event-emitting-button--close' ).trigger( 'click' );
			await nextTick();

			expect( wrapper.emitted( initEvents.cancel ) ).toBeFalsy();
		} );

		it( 'adds a class to show close button only on desktop if back button is available', () => {
			store.getters = {
				canGoToPreviousState: true,
				targetLabel: { value: 'P123', language: 'zxx' },
				config: { usePublish: false },
			};

			const wrapper = shallowMount( AppHeader, {
				global: {
					plugins: [ store ],
					stubs: { ProcessDialogHeader, EventEmittingButton },
				},
			} );

			expect(
				wrapper.find( '.app-header__close-button--desktop-only .wb-ui-event-emitting-button--close' ).exists(),
			).toBe( true );
		} );

		it( 'does not add a class limiting the close button to desktop if the back button is not available', () => {
			store.getters = {
				canGoToPreviousState: false,
				targetLabel: { value: 'P123', language: 'zxx' },
				config: { usePublish: false },
			};

			const wrapper = shallowMount( AppHeader, {
				global: {
					plugins: [ store ],
					stubs: { ProcessDialogHeader, EventEmittingButton },
				},
			} );

			expect(
				wrapper.find( '.app-header__close-button--desktop-only .wb-ui-event-emitting-button--close' ).exists(),
			).toBe( false );
		} );
	} );

	describe( 'back button rendering', () => {
		it( 'renders the back button with the correct message if it is allowed by the store', () => {
			const backMessage = 'go back!';
			const messageGet = jest.fn().mockReturnValue( backMessage );
			store.getters = {
				canGoToPreviousState: true,
				targetLabel: { value: 'P123', language: 'zxx' },
				config: { usePublish: false },
			};

			const wrapper = shallowMount( AppHeader, {
				global: {
					plugins: [ store ],
					mocks: {
						$messages: {
							KEYS: MessageKeys,
							get: messageGet,
							getText: messageGet,
						},
					},
					stubs: { ProcessDialogHeader, EventEmittingButton },
				},
			} );

			expect( messageGet ).toHaveBeenCalledWith( MessageKeys.ERROR_GO_BACK );
			const backButton = wrapper.findComponent<ComponentOptions>( '.wb-ui-event-emitting-button--back' );
			expect( backButton.exists() ).toBe( true );
			expect( backButton.props( 'message' ) ).toBe( backMessage );
		} );

		it( 'doesn\'t render the back button otherwise', () => {
			store.getters = {
				canGoToPreviousState: false,
				targetLabel: { value: 'P123', language: 'zxx' },
				config: { usePublish: false },
			};

			const wrapper = shallowMount( AppHeader, {
				global: {
					plugins: [ store ],
					stubs: { ProcessDialogHeader, EventEmittingButton },
				},
			} );

			expect( wrapper.findComponent( '.wb-ui-event-emitting-button--back' ).exists() ).toBe( false );
		} );
	} );

	describe( 'event handling', () => {

		it( 'bubbles the click event from the save button as save event', async () => {
			const wrapper = shallowMount( AppHeader, {
				global: {
					plugins: [ store ],
					stubs: { ProcessDialogHeader, EventEmittingButton },
				},
			} );

			const saveButton = wrapper.findComponent<ComponentOptions>(
				'.wb-ui-event-emitting-button--primaryProgressive',
			);
			saveButton.vm.$emit( 'click' );

			expect( wrapper.emitted( 'save' ) ).toHaveLength( 1 );
		} );

		it( 'bubbles the click event from the close button as close event', async () => {
			const wrapper = shallowMount( AppHeader, {
				global: {
					plugins: [ store ],
					stubs: { ProcessDialogHeader, EventEmittingButton },
				},
			} );

			const closeButton = wrapper.findComponent<ComponentOptions>( '.wb-ui-event-emitting-button--close' );
			closeButton.vm.$emit( 'click' );

			expect( wrapper.emitted( 'close' ) ).toHaveLength( 1 );
		} );

		it( 'bubbles the click event from the back button as back event', async () => {
			store.getters = {
				canGoToPreviousState: true,
				targetLabel: { value: 'P123', language: 'zxx' },
				config: { usePublish: false },
			};
			const wrapper = shallowMount( AppHeader, {
				global: {
					plugins: [ store ],
					stubs: { ProcessDialogHeader, EventEmittingButton },
				},
			} );

			const backButton = wrapper.findComponent<ComponentOptions>( '.wb-ui-event-emitting-button--back' );
			backButton.vm.$emit( 'click' );

			expect( wrapper.emitted( 'back' ) ).toHaveLength( 1 );
		} );

	} );

} );
