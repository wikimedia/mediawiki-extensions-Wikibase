import MessageKeys from '@/definitions/MessageKeys';
import ErrorSavingEditConflict from '@/presentation/components/ErrorSavingEditConflict.vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import { shallowMount } from '@vue/test-utils';

describe( 'ErrorSavingEditConflict', () => {
	const $messages = {
		KEYS: MessageKeys,
		getText: jest.fn( ( key: string ) => `⧼${key}⧽` ),
	};

	it( 'matches the snapshot', () => {
		const wrapper = shallowMount( ErrorSavingEditConflict, { mocks: { $messages } } );

		expect( wrapper.element ).toMatchSnapshot();
	} );

	it( 'maps reload button click to reload event', async () => {
		const wrapper = shallowMount( ErrorSavingEditConflict );

		wrapper.findComponent( EventEmittingButton ).vm.$emit( 'click' );
		expect( wrapper.emitted( 'reload' ) ).toHaveLength( 1 );
	} );
} );
