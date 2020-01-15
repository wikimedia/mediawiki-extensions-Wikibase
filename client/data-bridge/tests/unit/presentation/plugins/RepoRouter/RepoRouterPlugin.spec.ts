import MediaWikiRouter from '@/definitions/MediaWikiRouter';
import RepoRouterPlugin from '@/presentation/plugins/RepoRouterPlugin';
import { createLocalVue } from '@vue/test-utils';

describe( 'RepoRouterPlugin', () => {
	it( 'attaches MediaWikiRouter instance as $repoRouter', () => {
		const localVue = createLocalVue();
		const router: MediaWikiRouter = {
			getPageUrl: jest.fn(),
		};

		localVue.use( RepoRouterPlugin, router );

		expect( localVue.prototype.$repoRouter ).toBe( router );
	} );
} );
