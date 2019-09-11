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
			getReadingEntityRepository() {
				return {};
			},
			getWritingEntityRepository() {
				return {};
			},
			getEntityLabelRepository() {
				return {};
			},
		} as ServiceRepositories );
	} );

	it( 'mounts StringDataValue', () => {
		Vue.set( store, 'getters', {
			targetValue: { type: 'string', value: '' },
			targetProperty: 'P123',
			targetLabel: { value: 'P123', language: 'zxx' },
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
		const targetLabel = { value: 'P123', language: 'zxx' };
		Vue.set( store, 'getters', { targetValue, targetProperty, targetLabel } );

		const wrapper = shallowMount( DataBridge, {
			store,
			localVue,
		} );

		expect( wrapper.find( StringDataValue ).props( 'dataValue' ) ).toBe( targetValue );
		expect( wrapper.find( StringDataValue ).props( 'label' ) ).toBe( targetLabel );
	} );
} );
