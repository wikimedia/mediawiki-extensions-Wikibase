import DataPlaceholder from '@/presentation/components/DataPlaceholder.vue';
import { shallowMount } from '@vue/test-utils';

describe( 'DataPlaceholder', () => {
	it( 'is a Vue instance', () => {
		const wrapper = shallowMount( DataPlaceholder, {
			propsData: {
				entityId: 'Q123',
				propertyId: 'P456',
				editFlow: 'overwrite',
			},
		} );
		expect( wrapper.isVueInstance() ).toBeTruthy();
	} );

	it( 'renders correctly', () => {
		const wrapper = shallowMount( DataPlaceholder, {
			propsData: {
				entityId: 'Q123',
				propertyId: 'P456',
				editFlow: 'overwrite',
			},
		} );
		expect( wrapper.element ).toMatchSnapshot();
	} );

} );
