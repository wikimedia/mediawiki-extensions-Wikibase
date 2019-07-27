import DataPlaceholder from '@/presentation/components/DataPlaceholder.vue';
import { shallowMount } from '@vue/test-utils';

describe( 'DataPlaceholder', () => {
	it( 'renders correctly', () => {
		const wrapper = shallowMount( DataPlaceholder, {
			propsData: {
				targetValue: 'some string',
			},
		} );
		expect( wrapper.element ).toMatchSnapshot();
	} );
} );
