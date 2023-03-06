import EntityRevision from '@/datamodel/EntityRevision';
import { ErrorTypes } from '@/definitions/ApplicationError';
import ThankYou from '@/presentation/components/ThankYou.vue';
import WarningAnonymousEdit from '@/presentation/components/WarningAnonymousEdit.vue';
import { Store } from 'vuex';
import Entities from '@/mock-data/data/Q42.data.json';
import {
	shallowMount,
	config,
} from '@vue/test-utils';
import { nextTick } from 'vue';
import App from '@/presentation/App.vue';
import { createStore } from '@/store';
import Application from '@/store/Application';
import { initEvents, appEvents } from '@/events';
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
import newMockTracker from '../../util/newMockTracker';

beforeAll( () => {
	config.global.renderStubDefaultSlot = true;
} );

afterAll( () => {
	config.global.renderStubDefaultSlot = false;
} );

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
			'tracker': newMockTracker(),
			'editAuthorizationChecker': {
				canUseBridgeForItemAndPage: () => Promise.resolve( [] ),
			},
		} ) );

		const information = {
			entityId,
			propertyId,
			editFlow,
			originalHref: 'https://example.com',
			client: {
				usePublish: true,
			},
		};

		await store.dispatch( 'initBridge', information );
	} );

	const mockEmitter = { emit: jest.fn() };

	it( 'renders the mountable root element', () => {
		const wrapper = shallowMount( App, {
			global: {
				plugins: [ store ],
			},
			propsData: { emitter: mockEmitter },
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
			global: {
				plugins: [ localStore ],
			},
			propsData: { emitter: mockEmitter },
		} );

		wrapper.findComponent( AppHeader ).vm.$emit( 'back' );
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
			global: {
				plugins: [ localStore ],
				stubs: { EventEmittingButton },
			},
			propsData: { emitter: mockEmitter },
		} );

		wrapper.findComponent( AppHeader ).vm.$emit( 'save' );
		await nextTick();
		expect( bridgeSave ).not.toHaveBeenCalled();
		expect( wrapper.findComponent( License ).exists() ).toBe( true );

		wrapper.findComponent( AppHeader ).vm.$emit( 'save' );
		await nextTick();
		expect( bridgeSave ).toHaveBeenCalledTimes( 1 );
		expect( wrapper.emitted( initEvents.saved ) ).toBeFalsy();

		localStore.commit( 'setApplicationStatus', ApplicationStatus.SAVED );
		await nextTick();

		expect( wrapper.findComponent( ThankYou ).exists() ).toBe( true );
		wrapper.findComponent( ThankYou ).vm.$emit( 'opened-reference-edit-on-repo' );

		expect( mockEmitter.emit ).toHaveBeenCalledTimes( 1 );
		expect( mockEmitter.emit ).toHaveBeenCalledWith( initEvents.saved );
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
				global: {
					plugins: [ localStore ],
				},
				propsData: { emitter: mockEmitter },
			} );

			wrapper.findComponent( AppHeader ).vm.$emit( 'save' );
			await nextTick();
			expect( wrapper.findComponent( License ).exists() ).toBe( true );

			wrapper.findComponent( License ).vm.$emit( 'close' );
			await nextTick();
			expect( wrapper.findComponent( License ).exists() ).toBe( false );

			wrapper.findComponent( AppHeader ).vm.$emit( 'save' );
			await nextTick();
			expect( bridgeSave ).not.toHaveBeenCalled();
			expect( wrapper.findComponent( License ).exists() ).toBe( true );
		},
	);

	it( 'adds an overlay over DataBridge while showing the License', async () => {
		const wrapper = shallowMount( App, {
			global: {
				plugins: [ store ],
			},
			propsData: { emitter: mockEmitter },
		} );

		wrapper.findComponent( AppHeader ).vm.$emit( 'save' );
		await nextTick();

		expect( wrapper.findComponent( DataBridge ).classes( 'wb-db-app__data-bridge--overlayed' ) ).toBe( true );
	} );

	it( 'adds an overlay over DataBridge during save state', async () => {
		const wrapper = shallowMount( App, {
			global: {
				plugins: [ store ],
				stubs: { ProcessDialogHeader, EventEmittingButton },
			},
			propsData: { emitter: mockEmitter },
		} );

		store.commit( 'setApplicationStatus', ApplicationStatus.SAVING );
		await nextTick();

		expect( wrapper.findComponent( DataBridge ).classes( 'wb-db-app__data-bridge--overlayed' ) ).toBe( true );
	} );

	it( 'emits saved event on close button click after saving is done', async () => {
		const wrapper = shallowMount( App, {
			global: {
				plugins: [ store ],
			},
			propsData: { emitter: mockEmitter },
		} );

		store.commit( 'setApplicationStatus', ApplicationStatus.SAVED );

		wrapper.findComponent( AppHeader ).vm.$emit( 'close' );
		expect( mockEmitter.emit ).toHaveBeenCalledTimes( 1 );
		expect( mockEmitter.emit ).toHaveBeenCalledWith( initEvents.saved );
	} );

	it( 'cancels on close button click', async () => {
		const wrapper = shallowMount( App, {
			global: {
				plugins: [ store ],
			},
			propsData: { emitter: mockEmitter },
		} );

		wrapper.findComponent( AppHeader ).vm.$emit( 'close' );

		expect( mockEmitter.emit ).toHaveBeenCalledTimes( 1 );
		expect( mockEmitter.emit ).toHaveBeenCalledWith( initEvents.cancel );
	} );

	it( 'reloads on close button click during edit conflict', async () => {
		const wrapper = shallowMount( App, {
			global: {
				plugins: [ store ],
			},
			propsData: { emitter: mockEmitter },
		} );

		store.commit( 'addApplicationErrors', [ { type: ErrorTypes.EDIT_CONFLICT } ] );

		wrapper.findComponent( AppHeader ).vm.$emit( 'close' );

		expect( mockEmitter.emit ).toHaveBeenCalledTimes( 1 );
		expect( mockEmitter.emit ).toHaveBeenCalledWith( initEvents.reload );
	} );

	describe( 'component switch', () => {

		describe( 'if there is an error', () => {
			it( 'mounts ErrorWrapper', () => {
				store.commit( 'addApplicationErrors', [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ] );
				const wrapper = shallowMount( App, {
					global: {
						plugins: [ store ],
					},
					propsData: { emitter: mockEmitter },
				} );

				expect( wrapper.findComponent( ErrorWrapper ).exists() ).toBe( true );
			} );

			it.each( [
				[ 'relaunch', appEvents.relaunch ],
				[ 'reload', initEvents.reload ],
			] )( 'repeats %s ErrorWrapper event as %s init/app event', ( errorWrapperEvent, initAppEvent ) => {
				store.commit( 'addApplicationErrors', [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR } ] );
				const wrapper = shallowMount( App, {
					global: {
						plugins: [ store ],
					},
					propsData: { emitter: mockEmitter },
				} );
				wrapper.findComponent( ErrorWrapper ).vm.$emit( errorWrapperEvent );

				expect( mockEmitter.emit ).toHaveBeenCalledTimes( 1 );
				expect( mockEmitter.emit ).toHaveBeenCalledWith( initAppEvent );
			} );
		} );

		describe( 'outside of the error scenario', () => {
			it( 'mounts Loading & passes DataBridge to it', () => {
				store.commit( 'setApplicationStatus', ApplicationStatus.READY );
				const wrapper = shallowMount( App, {
					global: {
						plugins: [ store ],
					},
					propsData: { emitter: mockEmitter },
				} );

				expect( wrapper.findComponent( Loading ).exists() ).toBe( true );
				expect( wrapper.findComponent( DataBridge ).exists() ).toBe( true );
			} );

			it( 'instructs Loading accordingly if the store is not ready', () => {
				store.commit( 'setApplicationStatus', ApplicationStatus.INITIALIZING );
				const wrapper = shallowMount( App, {
					global: {
						plugins: [ store ],
					},
					propsData: { emitter: mockEmitter },
				} );

				expect( wrapper.findComponent( Loading ).props( 'isInitializing' ) ).toBe( true );
			} );

			it( 'instructs Loading accordingly if the store is attempting saving', () => {
				store.commit( 'setApplicationStatus', ApplicationStatus.SAVING );
				const wrapper = shallowMount( App, {
					global: {
						plugins: [ store ],
					},
					propsData: { emitter: mockEmitter },
				} );

				expect( wrapper.findComponent( Loading ).props( 'isSaving' ) ).toBe( true );
			} );

			it( 'instructs Loading accordingly if the store is ready', () => {
				store.commit( 'setApplicationStatus', ApplicationStatus.READY );
				const wrapper = shallowMount( App, {
					global: {
						plugins: [ store ],
					},
					propsData: { emitter: mockEmitter },
				} );

				expect( wrapper.findComponent( Loading ).props( 'isInitializing' ) ).toBe( false );
				expect( wrapper.findComponent( Loading ).props( 'isSaving' ) ).toBe( false );
			} );
		} );

		describe( 'if the user is not logged in', () => {
			it( 'mounts WarningAnonymousEdit, dismisses on click', async () => {
				const pageTitle = 'https://client.test/Page';
				const loginUrl = 'https://client.test/Login';
				store.commit( 'setShowWarningAnonymousEdit', true );
				store.commit( 'setPageTitle', pageTitle );
				store.commit( 'setApplicationStatus', ApplicationStatus.READY );
				const $clientRouter = {
					getPageUrl: jest.fn().mockReturnValue( loginUrl ),
				};
				const wrapper = shallowMount( App, {
					global: {
						plugins: [ store ],
						mocks: { $clientRouter },
					},
					propsData: { emitter: mockEmitter },
				} );

				expect( wrapper.findComponent( WarningAnonymousEdit ).exists() ).toBe( true );
				expect( wrapper.findComponent( Loading ).exists() ).toBe( false );
				expect( $clientRouter.getPageUrl ).toHaveBeenCalledWith(
					'Special:UserLogin',
					{
						returnto: pageTitle,
					},
				);
				expect( wrapper.findComponent( WarningAnonymousEdit ).props( 'loginUrl' ) ).toBe( loginUrl );

				wrapper.findComponent( WarningAnonymousEdit ).vm.$emit( 'proceed' );
				await nextTick();
				expect( wrapper.findComponent( WarningAnonymousEdit ).exists() ).toBe( false );
				expect( wrapper.findComponent( Loading ).exists() ).toBe( true );
			} );

			it( 'mounts WarningAnonymousEdit even if there are errors', () => {
				store.commit( 'setShowWarningAnonymousEdit', true );
				store.commit( 'setApplicationStatus', ApplicationStatus.READY );
				store.commit( 'addApplicationErrors', [
					{ type: ErrorTypes.INITIALIZATION_ERROR, info: {} },
				] );
				const $clientRouter = { getPageUrl: jest.fn().mockReturnValue( '' ) };
				const wrapper = shallowMount( App, {
					global: {
						plugins: [ store ],
						mocks: { $clientRouter },
					},
					propsData: { emitter: mockEmitter },
				} );

				expect( wrapper.findComponent( WarningAnonymousEdit ).exists() ).toBe( true );
				expect( wrapper.findComponent( ErrorWrapper ).exists() ).toBe( false );
			} );
		} );

	} );
} );
