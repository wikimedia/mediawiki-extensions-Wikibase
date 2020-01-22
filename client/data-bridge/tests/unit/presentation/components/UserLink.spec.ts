import UserLink from '@/presentation/components/UserLink.vue';
import { shallowMount } from '@vue/test-utils';

describe( 'UserLink', () => {
	it( 'links to the user page if given a positive user ID', () => {
		const userId = 1234;
		const userName = 'Example User';
		const router = {
			getPageUrl: jest.fn( ( title ) => `https://wiki.example/wiki/${title}` ),
		};
		const wrapper = shallowMount( UserLink, {
			propsData: { userId, userName, router },
		} );
		expect( wrapper.element ).toMatchSnapshot();
		expect( router.getPageUrl ).toHaveBeenCalledWith( `Special:Redirect/user/${userId}` );
	} );

	it( 'inserts only the user name if given a zero user ID', () => {
		const userName = 'Example User';
		const router = { getPageUrl: jest.fn() };
		const wrapper = shallowMount( UserLink, {
			propsData: { userId: 0, userName, router },
		} );
		expect( wrapper.element ).toMatchSnapshot();
		expect( router.getPageUrl ).not.toHaveBeenCalled();
	} );
} );
