import Vue from 'vue';
import DataBridge from '@/presentation/components/DataBridge.vue';
import {
	createLocalVue,
	shallowMount,
} from '@vue/test-utils';
import { createStore } from '@/store';
import Vuex, {
	Store,
} from 'vuex';
import DataPlaceholder from '@/presentation/components/DataPlaceholder.vue';
import Application from '@/store/Application';

let store: Store<Application>;
const localVue = createLocalVue();

localVue.use( Vuex );

describe( 'DataBridge', () => {
	beforeEach( () => {
		store = createStore();
	} );

	it( 'mounts DataPlaceholder', () => {
		const wrapper = shallowMount( DataBridge, {
			store,
			localVue,
		} );

		expect( wrapper.find( DataPlaceholder ).exists() ).toBeTruthy();
	} );

	it( 'delegates the targetValue to DataPlaceholder', () => {
		const targetValue = 'Töfften';
		Vue.set( store, 'getters', { targetValue } );

		const wrapper = shallowMount( DataBridge, {
			store,
			localVue,
		} );

		expect( wrapper.find( DataPlaceholder ).props( 'targetValue' ) ).toBe( targetValue );
	} );
} );
