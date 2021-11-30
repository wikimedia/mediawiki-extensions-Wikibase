import RepoRouterPlugin from '@/presentation/plugins/RepoRouterPlugin';
import MediaWikiRouter from '@/definitions/MediaWikiRouter';
import { App } from 'vue';

describe( 'RepoRouterPlugin', () => {
	it( 'attaches MediaWikiRouter instance as $repoRouter', () => {
		const router: MediaWikiRouter = {
			getPageUrl: jest.fn(),
		};

		const app = {
			config: { globalProperties: {} },
		} as App;

		RepoRouterPlugin( app, router );

		expect( app.config.globalProperties.$repoRouter ).toBe( router );
	} );
} );
