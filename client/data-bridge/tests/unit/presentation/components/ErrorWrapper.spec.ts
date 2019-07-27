import ErrorWrapper from '@/presentation/components/ErrorWrapper.vue';
import { shallowMount } from '@vue/test-utils';

describe( 'ErrorWrapper', () => {
	it( 'renders correctly', () => {
		const wrapper = shallowMount( ErrorWrapper );
		expect( wrapper.element ).toMatchSnapshot();
	} );
} );
