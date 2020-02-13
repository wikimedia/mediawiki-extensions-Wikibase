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
import StringDataValue from '@/presentation/components/StringDataValue.vue';
import ReferenceSection from '@/presentation/components/ReferenceSection.vue';
import EditDecision from '@/presentation/components/EditDecision.vue';
import Application from '@/store/Application';
import newMockServiceContainer from '../../services/newMockServiceContainer';

let store: Store<Application>;
const localVue = createLocalVue();

localVue.use( Vuex );

describe( 'DataBridge', () => {
	beforeEach( () => {
		store = createStore( newMockServiceContainer( {} ) );
		store.commit( 'setTargetValue', { type: 'string', value: '' } );
		Vue.set( store, 'getters', {
			targetLabel: { value: 'P123', language: 'zxx' },
			targetReferences: [],
		} );
	} );

	it( 'mounts StringDataValue', () => {
		const wrapper = shallowMount( DataBridge, {
			store,
			localVue,
		} );

		expect( wrapper.find( StringDataValue ).exists() ).toBe( true );
	} );

	it( 'delegates the necessary props to StringDataValue', () => {
		const targetValue = { type: 'string', value: 'Töfften' };
		const targetLabel = { value: 'P123', language: 'zxx' };
		const stringMaxLength = 200;
		store.commit( 'setTargetValue', targetValue );
		Vue.set( store.getters, 'targetLabel', targetLabel );

		const wrapper = shallowMount( DataBridge, {
			store,
			mocks: {
				$bridgeConfig: { stringMaxLength },
			},
			localVue,
		} );

		expect( wrapper.find( StringDataValue ).props( 'dataValue' ) ).toStrictEqual( targetValue );
		expect( wrapper.find( StringDataValue ).props( 'label' ) ).toBe( targetLabel );
		expect( wrapper.find( StringDataValue ).props( 'maxlength' ) ).toBe( stringMaxLength );
	} );

	it( 'mounts ReferenceSection', () => {
		const wrapper = shallowMount( DataBridge, {
			store,
			localVue,
		} );

		expect( wrapper.find( ReferenceSection ).exists() ).toBe( true );
	} );

	it( 'mounts EditDecision', () => {
		const wrapper = shallowMount( DataBridge, {
			store,
			localVue,
		} );

		expect( wrapper.find( EditDecision ).exists() ).toBe( true );
	} );
} );
