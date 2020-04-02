import ClientRouterPlugin from '@/presentation/plugins/ClientRouterPlugin';
import RepoRouterPlugin from '@/presentation/plugins/RepoRouterPlugin';

jest.mock( 'vue', () => {
	return {
		directive: jest.fn(),
		use: jest.fn(),
	};
} );

import Vue from 'vue';
import extendVueEnvironment from '@/presentation/extendVueEnvironment';
import MessagesPlugin from '@/presentation/plugins/MessagesPlugin';
import WikibaseClientConfiguration from '@/definitions/WikibaseClientConfiguration';
import BridgeConfig from '@/presentation/plugins/BridgeConfigPlugin';

const inlanguageDirective = {};
const mockInlanguage = jest.fn( ( _x: any ) => inlanguageDirective );
jest.mock( '@/presentation/directives/inlanguage', () => ( {
	__esModule: true,
	default: ( languageRepo: any ) => mockInlanguage( languageRepo ),
} ) );

describe( 'extendVueEnvironment', () => {
	it( 'attaches inlanguage directive', () => {
		const languageInfoRepo = new ( jest.fn() )();
		extendVueEnvironment(
			languageInfoRepo,
			new ( jest.fn() )(),
			{} as WikibaseClientConfiguration,
			new ( jest.fn() )(),
			new ( jest.fn() )(),
		);
		expect( mockInlanguage ).toHaveBeenCalledWith( languageInfoRepo );
		expect( Vue.directive ).toHaveBeenCalledWith( 'inlanguage', inlanguageDirective );
	} );

	it( 'invokes the Messages plugin', () => {
		const messageRepo = new ( jest.fn() )();
		extendVueEnvironment(
			new ( jest.fn() )(),
			messageRepo,
			{} as WikibaseClientConfiguration,
			new ( jest.fn() )(),
			new ( jest.fn() )(),
		);
		expect( Vue.use ).toHaveBeenCalledWith( MessagesPlugin, messageRepo );
	} );

	it( 'invokes the BridgeConfig plugin', () => {
		const config = { usePublish: true, issueReportingLink: '' };
		extendVueEnvironment(
			new ( jest.fn() )(),
			new ( jest.fn() )(),
			config,
			new ( jest.fn() )(),
			new ( jest.fn() )(),
		);

		expect( Vue.use ).toHaveBeenCalledWith( BridgeConfig, config );
	} );

	it( 'invokes the RepoRouterPlugin plugin', () => {
		const repoRouter = new ( jest.fn() )();
		extendVueEnvironment(
			new ( jest.fn() )(),
			new ( jest.fn() )(),
			{} as WikibaseClientConfiguration,
			repoRouter,
			new ( jest.fn() )(),
		);

		expect( Vue.use ).toHaveBeenCalledWith( RepoRouterPlugin, repoRouter );
	} );

	it( 'invokes the ClientRouterPlugin plugin', () => {
		const clientRouter = new ( jest.fn() )();
		extendVueEnvironment(
			new ( jest.fn() )(),
			new ( jest.fn() )(),
			{} as WikibaseClientConfiguration,
			new ( jest.fn() )(),
			clientRouter,
		);

		expect( Vue.use ).toHaveBeenCalledWith( ClientRouterPlugin, clientRouter );
	} );
} );
