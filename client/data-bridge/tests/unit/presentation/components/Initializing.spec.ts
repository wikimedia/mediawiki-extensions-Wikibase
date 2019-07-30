import Initializing from '@/presentation/components/Initializing.vue';
import { shallowMount } from '@vue/test-utils';

describe( 'Initializing', () => {
	it( 'is a Vue instance', () => {
		const wrapper = shallowMount( Initializing );
		expect( wrapper.isVueInstance() ).toBeTruthy();
	} );

	it( 'renders correctly', () => {
		const wrapper = shallowMount( Initializing );
		expect( wrapper.element ).toMatchSnapshot();
	} );

} );
