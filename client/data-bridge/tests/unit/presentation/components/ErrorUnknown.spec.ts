import { createLocalVue, shallowMount } from '@vue/test-utils';
import ErrorUnknown from '@/presentation/components/ErrorUnknown.vue';
import { createTestStore } from '../../../util/store';
import ApplicationError, { ErrorTypes } from '@/definitions/ApplicationError';

const localVue = createLocalVue();

describe( 'ErrorUnknown', () => {
	it( 'shows an (unoffical, hard-coded) generic error text', () => {
		const store = createTestStore( {
			state: {
				applicationErrors: [],
			},
		} );
		const wrapper = shallowMount( ErrorUnknown, { localVue, store } );
		expect( wrapper.find( ErrorUnknown ).html() ).toContain( 'An error occurred' );
	} );

	it( '(unofficially) shows the content of the applicationErrors state property', () => {
		const error: ApplicationError = {
			type: ErrorTypes.APPLICATION_LOGIC_ERROR,
			info: {
				stack: 'this is the stack trace',
			},
		};
		const store = createTestStore( {
			state: {
				applicationErrors: [ error ],
			},
		} );
		const wrapper = shallowMount( ErrorUnknown, { localVue, store } );
		expect( wrapper.find( 'pre' ).text() ).toContain( JSON.stringify( [ error ], null, 4 ) );
	} );
} );
