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
import Application from '@/store/Application';
import newMockServiceContainer from '../../services/newMockServiceContainer';

let store: Store<Application>;
const localVue = createLocalVue();

localVue.use( Vuex );

describe( 'DataBridge', () => {
	beforeEach( () => {
		store = createStore(
			newMockServiceContainer( {
				readingEntityRepository: {},
				writingEntityRepository: {},
				entityLabelRepository: {},
				wikibaseRepoConfigRepository: {},
				propertyDatatypeRepository: {},
				tracker: {},
			} ),
		);
	} );

	it( 'mounts StringDataValue', () => {
		Vue.set( store, 'getters', {
			targetValue: { type: 'string', value: '' },
			targetProperty: 'P123',
			targetLabel: { value: 'P123', language: 'zxx' },
			stringMaxLength: null,
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
		const stringMaxLength = 200;
		Vue.set( store, 'getters', { targetValue, targetProperty, targetLabel } );

		const wrapper = shallowMount( DataBridge, {
			store,
			mocks: {
				$bridgeConfig: { stringMaxLength },
			},
			localVue,
		} );

		expect( wrapper.find( StringDataValue ).props( 'dataValue' ) ).toBe( targetValue );
		expect( wrapper.find( StringDataValue ).props( 'label' ) ).toBe( targetLabel );
		expect( wrapper.find( StringDataValue ).props( 'maxlength' ) ).toBe( stringMaxLength );
	} );

	it( 'mounts ReferenceSection', () => {
		Vue.set( store, 'getters', {
			targetValue: { type: 'string', value: '' },
			targetProperty: 'P123',
			targetLabel: { value: 'P123', language: 'zxx' },
			targetReferences: [],
		} );
		const wrapper = shallowMount( DataBridge, {
			store,
			localVue,
		} );

		expect( wrapper.find( ReferenceSection ).exists() ).toBeTruthy();
	} );
} );
