import Vuex, { Store } from 'vuex';
import Entities from '@/mock-data/data/Q42.data.json';
import { createLocalVue, shallowMount } from '@vue/test-utils';
import App from '@/presentation/App.vue';
import { createStore } from '@/store';
import Application from '@/store/Application';
import {
	BRIDGE_INIT,
} from '@/store/actionTypes';
import {
	APPLICATION_STATUS_SET,
	EDITFLOW_SET,
	PROPERTY_TARGET_SET,
} from '@/store/mutationTypes';
import {
	NS_ENTITY,
} from '@/store/namespaces';
import {
	ENTITY_UPDATE,
} from '@/store/entity/mutationTypes';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import AppInformation from '@/definitions/AppInformation';
import EditFlow from '@/definitions/EditFlow';
import DataPlaceholder from '@/presentation/components/DataPlaceholder.vue';
import { services } from '@/services';
import namespacedStoreEvent from '@/store/namespacedStoreEvent';

const localVue = createLocalVue();
localVue.use( Vuex );

describe( 'App.vue', () => {
	let store: Store<Application>;
	let entityId: string;
	let propertyId: string;
	let editFlow: EditFlow;
	const getInformation = (): Promise<AppInformation> => {
		return Promise.resolve( {
			propertyId,
			entityId,
			editFlow,
		} );
	};

	beforeEach( () => {
		store = createStore();
		entityId = 'Q42';
		propertyId = 'P23';
		editFlow = EditFlow.OVERWRITE;

		services.setApplicationInformationRepository( {
			getInformation,
		} );

		services.setEntityRepository( {
			getEntity: () => {
				return Promise.resolve( {
					revisionId: 984899757,
					entity: Entities.entities.Q42,
				} as any );
			},
		} );
	} );

	it( 'renders the mountable root element', () => {
		const wrapper = shallowMount( App, {
			store,
			localVue,
		} );

		expect( wrapper.classes() ).toContain( 'wb-db-app' );
	} );

	it( 'mount Placeholder, when store is ready', () => {
		const wrapper = shallowMount( App, {
			store,
			localVue,
		} );
		expect( wrapper.find( DataPlaceholder ).exists() ).toBeTruthy();
	} );

	describe( 'property delegation', () => {
		beforeEach( () => {
			store.commit( APPLICATION_STATUS_SET, ApplicationStatus.READY );
		} );

		it( 'delegates entityId to the Placeholder', () => {
			entityId = 'Q123';
			store.commit(
				namespacedStoreEvent( NS_ENTITY, ENTITY_UPDATE ),
				{
					id: entityId,
					statements: {},
				},
			);
			const wrapper = shallowMount( App, {
				store,
				localVue,
			} );

			expect(
				wrapper.find( DataPlaceholder ).props( 'entityId' ),
			).toBe( entityId );
		} );

		it( 'delegates editFlow to the Placeholder', () => {
			editFlow = EditFlow.OVERWRITE;
			store.commit( EDITFLOW_SET, editFlow );

			const wrapper = shallowMount( App, {
				store,
				localVue,
			} );

			expect(
				wrapper.find( DataPlaceholder ).props( 'editFlow' ),
			).toBe( editFlow );
		} );

		it( 'delegates editFlow to the Placeholder', () => {
			propertyId = 'P42';
			store.commit( PROPERTY_TARGET_SET, propertyId );

			const wrapper = shallowMount( App, {
				store,
				localVue,
			} );

			expect(
				wrapper.find( DataPlaceholder ).props( 'propertyId' ),
			).toBe( propertyId );
		} );
	} );

	it( 'initalize the store on create', () => {
		store.dispatch = jest.fn( () => {
			return Promise.resolve();
		} );

		const wrapper = shallowMount( App, {// eslint-disable-line @typescript-eslint/no-unused-vars
			store,
			localVue,
		} );

		expect( store.dispatch ).toHaveBeenCalledTimes( 1 );
		expect(
			store.dispatch,
		).toHaveBeenCalledWith(
			BRIDGE_INIT,
		);
	} );
} );
