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
} from '@/store/actionTypes';
import {
	APPLICATION_STATUS_SET,
} from '@/store/mutationTypes';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import EditFlow from '@/definitions/EditFlow';
import DataBridge from '@/presentation/components/DataBridge.vue';
import Initializing from '@/presentation/components/Initializing.vue';
import ErrorWrapper from '@/presentation/components/ErrorWrapper.vue';
import ServiceRepositories from '@/services/ServiceRepositories';

const localVue = createLocalVue();
localVue.use( Vuex );

describe( 'App.vue', () => {
	let store: Store<Application>;
	let entityId: string;
	let propertyId: string;
	let editFlow: EditFlow;

	beforeEach( () => {
		entityId = 'Q42';
		propertyId = 'P349';
		editFlow = EditFlow.OVERWRITE;
		const services = new ServiceRepositories();
		services.setEntityRepository( {
			getEntity: () => {
				return Promise.resolve( {
					revisionId: 984899757,
					entity: Entities.entities.Q42,
				} as any );
			},
		} );

		store = createStore( services );

		const information = {
			entityId,
			propertyId,
			editFlow,
		};

		store.dispatch( BRIDGE_INIT, information );
	} );

	it( 'renders the mountable root element', () => {
		const wrapper = shallowMount( App, {
			store,
			localVue,
		} );

		expect( wrapper.classes() ).toContain( 'wb-db-app' );
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

		it( 'mounts ErrorWrapper, if a error occures', () => {
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
