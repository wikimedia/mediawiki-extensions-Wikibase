import EntityRevision from '@/datamodel/EntityRevision';
import { ErrorTypes } from '@/definitions/ApplicationError';
import ThankYou from '@/presentation/components/ThankYou.vue';
import Vuex, { Store } from 'vuex';
import Entities from '@/mock-data/data/Q42.data.json';
import {
	createLocalVue,
	shallowMount,
} from '@vue/test-utils';
import App from '@/presentation/App.vue';
import { createStore } from '@/store';
import Application from '@/store/Application';
import { initEvents } from '@/events';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import EditFlow from '@/definitions/EditFlow';
import DataBridge from '@/presentation/components/DataBridge.vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import Loading from '@/presentation/components/Loading.vue';
import ErrorWrapper from '@/presentation/components/ErrorWrapper.vue';
import ProcessDialogHeader from '@/presentation/components/ProcessDialogHeader.vue';
import hotUpdateDeep from '@wmde/vuex-helpers/dist/hotUpdateDeep';
import EntityId from '@/datamodel/EntityId';
import newMockServiceContainer from '../services/newMockServiceContainer';
import License from '@/presentation/components/License.vue';
import AppHeader from '@/presentation/components/AppHeader.vue';

const localVue = createLocalVue();
localVue.use( Vuex );

describe( 'App.vue', () => {
	let store: Store<Application>;
	let entityId: EntityId;
	let propertyId: string;
	let editFlow: EditFlow;

	beforeEach( async () => {
		entityId = 'Q42';
		propertyId = 'P373';
		editFlow = EditFlow.SINGLE_BEST_VALUE;
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
			'referencesRenderingRepository': {
				getRenderedReferences: () => Promise.resolve( [] ),
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

		await store.dispatch( 'initBridge', information );
	} );

	it( 'renders the mountable root element', () => {
		const wrapper = shallowMount( App, {
			store,
			localVue,
		} );

		expect( wrapper.classes() ).toContain( 'wb-db-app' );
	} );

	it( 'goes back if the back button is clicked', async () => {
		const goBackFromErrorToReady = jest.fn();
		const localStore = hotUpdateDeep( store, {
			actions: {
				goBackFromErrorToReady,
			},
		} );
		const wrapper = shallowMount( App, {
			store: localStore,
			localVue,
		} );

		await wrapper.find( AppHeader ).vm.$emit( 'back' );
		await localVue.nextTick();
		expect( goBackFromErrorToReady ).toHaveBeenCalled();
	} );

	it( 'shows License on 1st save click, saves on 2nd save click, emits on refs click', async () => {
		const bridgeSave = jest.fn();
		const localStore = hotUpdateDeep( store, {
			actions: {
				saveBridge: bridgeSave,
			},
		} );
		localStore.commit( 'setApplicationStatus', ApplicationStatus.READY );
		const wrapper = shallowMount( App, {
			store: localStore,
			localVue,
			stubs: { EventEmittingButton },
		} );

		await wrapper.find( AppHeader ).vm.$emit( 'save' );
		await localVue.nextTick();
		expect( bridgeSave ).not.toHaveBeenCalled();
		expect( wrapper.find( License ).exists() ).toBe( true );

		await wrapper.find( AppHeader ).vm.$emit( 'save' );
		await localVue.nextTick();
		expect( bridgeSave ).toHaveBeenCalledTimes( 1 );
		expect( wrapper.emitted( initEvents.saved ) ).toBeFalsy();

		localStore.commit( 'setApplicationStatus', ApplicationStatus.SAVED );
		expect( wrapper.find( ThankYou ).exists() ).toBe( true );
		await wrapper.find( ThankYou ).vm.$emit( 'opened-reference-edit-on-repo' );
		await localVue.nextTick();
		expect( wrapper.emitted( initEvents.saved ) ).toHaveLength( 1 );
	} );

	it(
		'dismisses License on License\'s close button click and shows it again on next save button click',
		async () => {
			const bridgeSave = jest.fn();
			const localStore = hotUpdateDeep( store, {
				actions: {
					saveBridge: bridgeSave,
				},
			} );
			localStore.commit( 'setApplicationStatus', ApplicationStatus.READY );
			const wrapper = shallowMount( App, {
				store: localStore,
				localVue,
			} );

			await wrapper.find( AppHeader ).vm.$emit( 'save' );
			await localVue.nextTick();
			expect( wrapper.find( License ).exists() ).toBe( true );

			await wrapper.find( License ).vm.$emit( 'close' );
			await localVue.nextTick();
			expect( wrapper.find( License ).exists() ).toBe( false );

			await wrapper.find( AppHeader ).vm.$emit( 'save' );
			await localVue.nextTick();
			expect( bridgeSave ).not.toHaveBeenCalled();
			expect( wrapper.find( License ).exists() ).toBe( true );
		},
	);

	it( 'adds an overlay over DataBridge while showing the License', async () => {
		const wrapper = shallowMount( App, {
			store,
			localVue,
		} );

		await wrapper.find( AppHeader ).vm.$emit( 'save' );
		await localVue.nextTick();

		expect( wrapper.find( DataBridge ).classes( 'wb-db-app__data-bridge--overlayed' ) ).toBe( true );
	} );

	it( 'adds an overlay over DataBridge during save state', async () => {
		const wrapper = shallowMount( App, {
			store,
			localVue,
			stubs: { ProcessDialogHeader, EventEmittingButton },
		} );

		store.commit( 'setApplicationStatus', ApplicationStatus.SAVING );

		expect( wrapper.find( DataBridge ).classes( 'wb-db-app__data-bridge--overlayed' ) ).toBe( true );
	} );

	it( 'emits saved event on close button click after saving is done', async () => {
		const wrapper = shallowMount( App, {
			store,
			localVue,
		} );

		store.commit( 'setApplicationStatus', ApplicationStatus.SAVED );

		await wrapper.find( AppHeader ).vm.$emit( 'close' );
		await localVue.nextTick();

		expect( wrapper.emitted( initEvents.saved ) ).toHaveLength( 1 );
	} );

	it( 'cancels on close button click', async () => {
		const wrapper = shallowMount( App, {
			store,
			localVue,
		} );

		await wrapper.find( AppHeader ).vm.$emit( 'close' );
		await localVue.nextTick();

		expect( wrapper.emitted( initEvents.cancel ) ).toHaveLength( 1 );
	} );

	describe( 'component switch', () => {

		describe( 'if there is an error', () => {
			it( 'mounts ErrorWrapper', () => {
				store.commit( 'addApplicationErrors', [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ] );
				const wrapper = shallowMount( App, {
					store,
					localVue,
				} );

				expect( wrapper.find( ErrorWrapper ).exists() ).toBe( true );
			} );
		} );

		describe( 'outside of the error scenario', () => {
			it( 'mounts Loading & passes DataBridge to it', () => {
				store.commit( 'setApplicationStatus', ApplicationStatus.READY );
				const wrapper = shallowMount( App, {
					store,
					localVue,
				} );

				expect( wrapper.find( Loading ).exists() ).toBe( true );
				expect( wrapper.find( Loading ).find( DataBridge ).exists() ).toBe( true );
			} );

			it( 'instructs Loading accordingly if the store is not ready', () => {
				store.commit( 'setApplicationStatus', ApplicationStatus.INITIALIZING );
				const wrapper = shallowMount( App, {
					store,
					localVue,
				} );

				expect( wrapper.find( Loading ).props( 'isInitializing' ) ).toBe( true );
			} );

			it( 'instructs Loading accordingly if the store is attempting saving', () => {
				store.commit( 'setApplicationStatus', ApplicationStatus.SAVING );
				const wrapper = shallowMount( App, {
					store,
					localVue,
				} );

				expect( wrapper.find( Loading ).props( 'isSaving' ) ).toBe( true );
			} );

			it( 'instructs Loading accordingly if the store is ready', () => {
				store.commit( 'setApplicationStatus', ApplicationStatus.READY );
				const wrapper = shallowMount( App, {
					store,
					localVue,
				} );

				expect( wrapper.find( Loading ).props( 'isInitializing' ) ).toBe( false );
				expect( wrapper.find( Loading ).props( 'isSaving' ) ).toBe( false );
			} );
		} );

	} );
} );
