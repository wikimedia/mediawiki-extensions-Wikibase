import EntityRevision from '@/datamodel/EntityRevision';
import { ErrorTypes } from '@/definitions/ApplicationError';
import Vuex, { Store } from 'vuex';
import Entities from '@/mock-data/data/Q42.data.json';
import {
	createLocalVue,
	shallowMount,
} from '@vue/test-utils';
import App from '@/presentation/App.vue';
import { createStore } from '@/store';
import Application from '@/store/Application';
import {
	BRIDGE_INIT,
	BRIDGE_SAVE,
} from '@/store/actionTypes';
import {
	APPLICATION_ERRORS_ADD,
	APPLICATION_STATUS_SET,
} from '@/store/mutationTypes';
import Events from '@/events';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import EditFlow from '@/definitions/EditFlow';
import DataBridge from '@/presentation/components/DataBridge.vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import Initializing from '@/presentation/components/Initializing.vue';
import ErrorWrapper from '@/presentation/components/ErrorWrapper.vue';
import ProcessDialogHeader from '@/presentation/components/ProcessDialogHeader.vue';
import hotUpdateDeep from '@wmde/vuex-helpers/dist/hotUpdateDeep';
import MessageKeys from '@/definitions/MessageKeys';
import EntityId from '@/datamodel/EntityId';
import newMockServiceContainer from '../services/newMockServiceContainer';

const localVue = createLocalVue();
localVue.use( Vuex );

describe( 'App.vue', () => {
	let store: Store<Application>;
	let entityId: EntityId;
	let propertyId: string;
	let editFlow: EditFlow;

	beforeEach( async () => {
		entityId = 'Q42';
		propertyId = 'P349';
		editFlow = EditFlow.OVERWRITE;
		( Entities.entities.Q42 as any ).statements = Entities.entities.Q42.claims;

		store = createStore( newMockServiceContainer( {
			'readingEntityRepository': {
				getEntity: () => Promise.resolve( {
					revisionId: 984899757,
					entity: Entities.entities.Q42,
				} as any ),
			},
			'writingEntityRepository': {
				saveEntity: ( entity: EntityRevision ) => Promise.resolve( new EntityRevision(
					entity.entity,
					entity.revisionId + 1,
				) ),
			},
			'entityLabelRepository': {
				getLabel: () => Promise.reject(),
			},
			'wikibaseRepoConfigRepository': {
				getRepoConfiguration: () => Promise.resolve( {
					dataTypeLimits: {
						string: {
							maxLength: 200,
						},
					},
				} ),
			},
			'propertyDatatypeRepository': {
				getDataType: jest.fn().mockResolvedValue( 'string' ),
			},
			'tracker': {
				trackPropertyDatatype: jest.fn(),
			},
			'editAuthorizationChecker': {
				canUseBridgeForItemAndPage: () => Promise.resolve( [] ),
			},
		} ) );

		const information = {
			entityId,
			propertyId,
			editFlow,
			client: {
				usePublish: true,
			},
		};

		await store.dispatch( BRIDGE_INIT, information );
	} );

	it( 'renders the mountable root element', () => {
		const wrapper = shallowMount( App, {
			store,
			localVue,
		} );

		expect( wrapper.classes() ).toContain( 'wb-db-app' );
	} );

	it( 'shows the header with title', () => {
		const titleMessage = 'he ho';
		const messageGet = jest.fn().mockReturnValue( titleMessage );
		const wrapper = shallowMount( App, {
			store,
			localVue,
			mocks: {
				$messages: {
					KEYS: MessageKeys,
					get: messageGet,
				},
			},
		} );

		expect( wrapper.find( ProcessDialogHeader ).exists() ).toBeTruthy();
		expect( messageGet ).toHaveBeenCalledWith( MessageKeys.BRIDGE_DIALOG_TITLE );
		expect( wrapper.find( ProcessDialogHeader ).props( 'title' ) ).toBe( titleMessage );
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

			const wrapper = shallowMount( App, {
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

			const wrapper = shallowMount( App, {
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

	it( 'saves on save button click', async () => {
		const bridgeSave = jest.fn();
		const localStore = hotUpdateDeep( store, {
			actions: {
				[ BRIDGE_SAVE ]: bridgeSave,
			},
		} );
		localStore.commit( APPLICATION_STATUS_SET, ApplicationStatus.READY );
		const wrapper = shallowMount( App, {
			store: localStore,
			localVue,
			stubs: { ProcessDialogHeader, EventEmittingButton },
		} );

		await wrapper.find( '.wb-ui-event-emitting-button--primaryProgressive' ).vm.$emit( 'click' );
		await localVue.nextTick();

		expect( bridgeSave ).toHaveBeenCalledTimes( 1 );
		expect( wrapper.emitted( Events.onSaved ) ).toBeTruthy();
	} );

	it( 'renders the cancel button using the CANCEL message', () => {
		const cancelMessage = 'cancel that';
		const messageGet = jest.fn().mockReturnValue( cancelMessage );
		const wrapper = shallowMount( App, {
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
		const button = wrapper.find( '.wb-ui-event-emitting-button--cancel' );
		expect( button.props( 'message' ) ).toBe( cancelMessage );
	} );

	it( 'cancels on cancel button click', async () => {
		const wrapper = shallowMount( App, {
			store,
			localVue,
			stubs: { ProcessDialogHeader, EventEmittingButton },
		} );

		await wrapper.find( '.wb-ui-event-emitting-button--cancel' ).vm.$emit( 'click' );
		await localVue.nextTick();

		expect( wrapper.emitted( Events.onCancel ) ).toBeTruthy();
	} );

	describe( 'component switch', () => {
		it( 'mounts ErrorWrapper, if a error occurs', () => {
			store.commit( APPLICATION_ERRORS_ADD, [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ] );
			const wrapper = shallowMount( App, {
				store,
				localVue,
			} );

			expect( wrapper.find( ErrorWrapper ).exists() ).toBeTruthy();
		} );

		describe( 'outside of the error scenario', () => {
			it( 'mounts Initializing & passes DataBridge to it', () => {
				store.commit( APPLICATION_STATUS_SET, ApplicationStatus.READY );
				const wrapper = shallowMount( App, {
					store,
					localVue,
				} );

				expect( wrapper.find( Initializing ).exists() ).toBeTruthy();
				expect( wrapper.find( Initializing ).find( DataBridge ).exists() ).toBeTruthy();
			} );

			it( 'instructs Initializing accordingly if the store is not ready', () => {
				store.commit( APPLICATION_STATUS_SET, ApplicationStatus.INITIALIZING );
				const wrapper = shallowMount( App, {
					store,
					localVue,
				} );

				expect( wrapper.find( Initializing ).props( 'isInitializing' ) ).toBe( true );
			} );

			it( 'instructs Initializing accordingly if the store is ready', () => {
				store.commit( APPLICATION_STATUS_SET, ApplicationStatus.READY );
				const wrapper = shallowMount( App, {
					store,
					localVue,
				} );

				expect( wrapper.find( Initializing ).props( 'isInitializing' ) ).toBe( false );
			} );
		} );

	} );
} );
