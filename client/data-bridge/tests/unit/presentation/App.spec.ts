import EntityRevision from '@/datamodel/EntityRevision';
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
	APPLICATION_STATUS_SET,
} from '@/store/mutationTypes';
import Events from '@/events';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import EditFlow from '@/definitions/EditFlow';
import DataBridge from '@/presentation/components/DataBridge.vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import Initializing from '@/presentation/components/Initializing.vue';
import ErrorWrapper from '@/presentation/components/ErrorWrapper.vue';
import ServiceRepositories from '@/services/ServiceRepositories';
import ProcessDialogHeader from '@/presentation/components/ProcessDialogHeader.vue';
import hotUpdateDeep from '@wmde/vuex-helpers/dist/hotUpdateDeep';
import MessageKeys from '@/definitions/MessageKeys';
import EntityId from '@/datamodel/EntityId';

const localVue = createLocalVue();
localVue.use( Vuex );

describe( 'App.vue', () => {
	let store: Store<Application>;
	let entityId: EntityId;
	let propertyId: string;
	let editFlow: EditFlow;
	const services = new ServiceRepositories();

	beforeEach( async () => {
		entityId = 'Q42';
		propertyId = 'P349';
		editFlow = EditFlow.OVERWRITE;
		( Entities.entities.Q42 as any ).statements = Entities.entities.Q42.claims;

		services.setReadingEntityRepository( {
			getEntity: () => {
				return Promise.resolve( {
					revisionId: 984899757,
					entity: Entities.entities.Q42,
				} as any );
			},
		} );
		services.setWritingEntityRepository( {
			saveEntity( entity: EntityRevision ): Promise<EntityRevision> {
				return Promise.resolve( new EntityRevision(
					entity.entity,
					entity.revisionId + 1,
				) );
			},
		} );
		services.setEntityLabelRepository( {
			getLabel( _id ) {
				return Promise.reject();
			},
		} );

		store = createStore( services );

		const information = {
			entityId,
			propertyId,
			editFlow,
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

	it( 'renders the save button using the SAVE_CHANGES message', () => {
		const saveMessage = 'go go go';
		const messageGet = jest.fn().mockReturnValue( saveMessage );
		const wrapper = shallowMount( App, {
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

		expect( messageGet ).toHaveBeenCalledWith( MessageKeys.SAVE_CHANGES );
		expect( wrapper.find( EventEmittingButton ).props( 'message' ) ).toBe( saveMessage );
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

	describe( 'component switch', () => {
		it( 'mounts DataBridge, when store is ready', () => {
			store.commit( APPLICATION_STATUS_SET, ApplicationStatus.READY );
			const wrapper = shallowMount( App, {
				store,
				localVue,
			} );

			expect( wrapper.find( DataBridge ).exists() ).toBeTruthy();
		} );

		it( 'mounts ErrorWrapper, if a error occurs', () => {
			store.commit( APPLICATION_STATUS_SET, ApplicationStatus.ERROR );
			const wrapper = shallowMount( App, {
				store,
				localVue,
			} );

			expect( wrapper.find( ErrorWrapper ).exists() ).toBeTruthy();
		} );

		it( 'mounts Initializing, if the store is not ready', () => {
			store.commit( APPLICATION_STATUS_SET, ApplicationStatus.INITIALIZING );
			const wrapper = shallowMount( App, {
				store,
				localVue,
			} );

			expect( wrapper.find( Initializing ).exists() ).toBeTruthy();
		} );
	} );
} );
