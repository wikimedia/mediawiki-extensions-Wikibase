import ClientRouterPlugin from '@/presentation/plugins/ClientRouterPlugin';
import RepoRouterPlugin from '@/presentation/plugins/RepoRouterPlugin';

jest.mock( 'vue', () => {
	return {
		directive: jest.fn(),
	};
} );

import Vue from 'vue';
import extendVueEnvironment from '@/presentation/extendVueEnvironment';
import MessagesPlugin from '@/presentation/plugins/MessagesPlugin';

const inlanguageDirective = {};
const mockInlanguage = jest.fn( ( _x: any ) => inlanguageDirective );
jest.mock( '@/presentation/directives/inlanguage', () => ( {
	__esModule: true,
	default: ( languageRepo: any ) => mockInlanguage( languageRepo ),
} ) );

const appMock: any = {
	use: jest.fn(),
};

describe( 'extendVueEnvironment', () => {
	it( 'attaches inlanguage directive', () => {
		const languageInfoRepo = new ( jest.fn() )();
		extendVueEnvironment(
			appMock,
			languageInfoRepo,
			new ( jest.fn() )(),
			new ( jest.fn() )(),
			new ( jest.fn() )(),
		);
		expect( mockInlanguage ).toHaveBeenCalledWith( languageInfoRepo );
		expect( Vue.directive ).toHaveBeenCalledWith( 'inlanguage', inlanguageDirective );
	} );

	it( 'invokes the Messages plugin', () => {
		const messageRepo = new ( jest.fn() )();
		extendVueEnvironment(
			appMock,
			new ( jest.fn() )(),
			messageRepo,
			new ( jest.fn() )(),
			new ( jest.fn() )(),
		);
		expect( appMock.use ).toHaveBeenCalledWith( MessagesPlugin, messageRepo );
	} );

	it( 'invokes the RepoRouterPlugin plugin', () => {
		const repoRouter = new ( jest.fn() )();
		extendVueEnvironment(
			appMock,
			new ( jest.fn() )(),
			new ( jest.fn() )(),
			repoRouter,
			new ( jest.fn() )(),
		);

		expect( appMock.use ).toHaveBeenCalledWith( RepoRouterPlugin, repoRouter );
	} );

	it( 'invokes the ClientRouterPlugin plugin', () => {
		const clientRouter = new ( jest.fn() )();
		extendVueEnvironment(
			appMock,
			new ( jest.fn() )(),
			new ( jest.fn() )(),
			new ( jest.fn() )(),
			clientRouter,
		);

		expect( appMock.use ).toHaveBeenCalledWith( ClientRouterPlugin, clientRouter );
	} );
} );
