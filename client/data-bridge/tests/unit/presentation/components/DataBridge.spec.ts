import DataBridge from '@/presentation/components/DataBridge.vue';
import {
	shallowMount,
} from '@vue/test-utils';
import { createStore } from '@/store';
import StringDataValue from '@/presentation/components/StringDataValue.vue';
import ReferenceSection from '@/presentation/components/ReferenceSection.vue';
import EditDecision from '@/presentation/components/EditDecision.vue';
import Application from '@/store/Application';
import newMockServiceContainer from '../../services/newMockServiceContainer';
import { MutableStore } from '../../../util/store';

let store: MutableStore<Application>;

describe( 'DataBridge', () => {
	beforeEach( () => {
		store = createStore( newMockServiceContainer( {} ) );
		store.commit( 'setTargetValue', { type: 'string', value: '' } );
		store.getters = {
			targetLabel: { value: 'P123', language: 'zxx' },
			targetReferences: [],
			config: {
				stringMaxLength: 123,
			},
		};
	} );

	it( 'mounts StringDataValue', () => {
		const wrapper = shallowMount( DataBridge, {
			global: { plugins: [ store ] },
		} );

		expect( wrapper.findComponent( StringDataValue ).exists() ).toBe( true );
	} );

	it( 'delegates the necessary props to StringDataValue', () => {
		const targetValue = { type: 'string', value: 'Töfften' };
		const targetLabel = { value: 'P123', language: 'zxx' };
		const stringMaxLength = 200;
		store.commit( 'setTargetValue', targetValue );
		store.getters.targetLabel = targetLabel;
		store.getters.config = { stringMaxLength };

		const wrapper = shallowMount( DataBridge, {
			global: { plugins: [ store ] },
		} );

		expect( wrapper.findComponent( StringDataValue ).props( 'dataValue' ) ).toStrictEqual( targetValue );
		expect( wrapper.findComponent( StringDataValue ).props( 'label' ) ).toBe( targetLabel );
		expect( wrapper.findComponent( StringDataValue ).props( 'maxlength' ) ).toBe( stringMaxLength );
	} );

	it( 'mounts ReferenceSection', () => {
		const wrapper = shallowMount( DataBridge, {
			global: { plugins: [ store ] },
		} );

		expect( wrapper.findComponent( ReferenceSection ).exists() ).toBe( true );
	} );

	it( 'mounts EditDecision', () => {
		const wrapper = shallowMount( DataBridge, {
			global: { plugins: [ store ] },
		} );

		expect( wrapper.findComponent( EditDecision ).exists() ).toBe( true );
	} );
} );
