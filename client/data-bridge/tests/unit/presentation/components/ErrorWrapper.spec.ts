import ErrorWrapper from '@/presentation/components/ErrorWrapper.vue';
import { shallowMount } from '@vue/test-utils';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';

describe( 'ErrorWrapper', () => {
	it( 'renders correctly', () => {
		const wrapper = shallowMount( ErrorWrapper );
		expect( wrapper.element ).toMatchSnapshot();
	} );

	it( 'shows the repository about-link', () => {
		const aboutArticleUrl = 'http://localhost/wiki/Project:About';
		const $repoRouter: MediaWikiRouter = {
			getPageUrl: jest.fn().mockReturnValue( aboutArticleUrl ),
		};
		const wrapper = shallowMount( ErrorWrapper, {
			mocks: {
				$repoRouter,
			},
		} );

		expect( $repoRouter.getPageUrl ).toHaveBeenCalledWith( 'Project:About' );
		expect( wrapper.find( 'a' ).attributes( 'href' ) )
			.toBe( aboutArticleUrl );
	} );
} );
