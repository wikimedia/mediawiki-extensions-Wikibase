import { shallowMount } from '@vue/test-utils';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';
import PageList from '@/presentation/components/PageList.vue';

describe( 'PageList', () => {
	const router: MediaWikiRouter = {
		getPageUrl: jest.fn().mockImplementation( ( title ) => `https://wiki/${title}` ),
	};
	const pages = [ 'Foo', 'Bar', 'Baz' ];

	it( 'builds a list matching our snapshot', () => {
		const wrapper = shallowMount( PageList, {
			propsData: {
				router,
				pages,
			},
		} );
		expect( wrapper.element ).toMatchSnapshot();
	} );

	it( 'calls the router to build the link for each page', () => {
		shallowMount( PageList, {
			propsData: {
				router,
				pages,
			},
		} );
		expect( router.getPageUrl ).toHaveBeenCalledTimes( pages.length );
		expect( router.getPageUrl ).toHaveBeenNthCalledWith( 1, pages[ 0 ] );
		expect( router.getPageUrl ).toHaveBeenNthCalledWith( 2, pages[ 1 ] );
		expect( router.getPageUrl ).toHaveBeenNthCalledWith( 3, pages[ 2 ] );
	} );
} );
