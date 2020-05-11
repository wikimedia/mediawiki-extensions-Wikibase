import MessageKeys from '@/definitions/MessageKeys';
import ErrorSavingAssertUser from '@/presentation/components/ErrorSavingAssertUser.vue';
import {
	shallowMount,
} from '@vue/test-utils';

describe( 'ErrorSavingAssertUser', () => {

	it( 'matches the snapshot', () => {
		const $messages = {
			KEYS: MessageKeys,
			get: jest.fn().mockReturnValue( 'Test <abbr>HTML</abbr>.' ),
		};

		const wrapper = shallowMount( ErrorSavingAssertUser, {
			propsData: {
				loginUrl: 'https://data-bridge.test/Login',
			},
			mocks: { $messages },
		} );

		expect( wrapper.element ).toMatchSnapshot();
	} );

} );
