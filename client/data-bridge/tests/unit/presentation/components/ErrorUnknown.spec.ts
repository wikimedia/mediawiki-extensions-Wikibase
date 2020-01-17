import { createLocalVue, shallowMount } from '@vue/test-utils';
import ErrorUnknown from '@/presentation/components/ErrorUnknown.vue';

const localVue = createLocalVue();

describe( 'ErrorUnknown', () => {
	it( 'shows an (unoffical, hard-coded) generic error text', () => {
		const wrapper = shallowMount( ErrorUnknown, { localVue } );
		expect( wrapper.find( ErrorUnknown ).html() ).toContain( 'An error occurred' );
	} );
} );
