import IndeterminateProgressBar from '@/presentation/components/IndeterminateProgressBar.vue';
import { shallowMount } from '@vue/test-utils';

describe( 'IndeterminateProgressBar', () => {
	it( 'renders correctly', () => {
		const wrapper = shallowMount( IndeterminateProgressBar );
		expect( wrapper.element ).toMatchSnapshot();
	} );
} );
