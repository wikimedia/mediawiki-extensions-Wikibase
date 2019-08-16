import Vue from 'vue';
import ServiceRepositories from '@/services/ServiceRepositories';
import DataBridge from '@/presentation/components/DataBridge.vue';
import {
	createLocalVue,
	shallowMount,
} from '@vue/test-utils';
import { createStore } from '@/store';
import Vuex, {
	Store,
} from 'vuex';
import StringDataValue from '@/presentation/components/StringDataValue.vue';
import Application from '@/store/Application';

let store: Store<Application>;
const localVue = createLocalVue();

localVue.use( Vuex );

describe( 'DataBridge', () => {
	beforeEach( () => {
		store = createStore( {
			getEntityRepository() {
				return {};
			},
		} as ServiceRepositories );
	} );

	it( 'mounts StringDataValue', () => {
		Vue.set( store, 'getters', {
			targetValue: { type: 'string', value: '' },
			targetProperty: 'P123',
		} );
		const wrapper = shallowMount( DataBridge, {
			store,
			localVue,
		} );

		expect( wrapper.find( StringDataValue ).exists() ).toBeTruthy();
	} );

	it( 'delegates the necessary props to StringDataValue', () => {
		const targetValue = { type: 'string', value: 'Töfften' };
		const targetProperty = 'P123';
		Vue.set( store, 'getters', { targetValue, targetProperty } );

		const wrapper = shallowMount( DataBridge, {
			store,
			localVue,
		} );

		expect( wrapper.find( StringDataValue ).props( 'dataValue' ) ).toBe( targetValue );
		expect( wrapper.find( StringDataValue ).props( 'label' ) ).toBe( targetProperty );
	} );
} );
