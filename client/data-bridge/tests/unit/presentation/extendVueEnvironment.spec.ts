import ClientRouterPlugin from '@/presentation/plugins/ClientRouterPlugin';
import RepoRouterPlugin from '@/presentation/plugins/RepoRouterPlugin';
import extendVueEnvironment from '@/presentation/extendVueEnvironment';
import MessagesPlugin from '@/presentation/plugins/MessagesPlugin';
import InLanguagePlugin from '@/presentation/plugins/InLanguagePlugin';

const appMock: any = {
	use: jest.fn(),
};

describe( 'extendVueEnvironment', () => {
	it( 'invokes the InLanguage plugin', () => {
		const languageInfoRepo = new ( jest.fn() )();
		extendVueEnvironment(
			appMock,
			languageInfoRepo,
			new ( jest.fn() )(),
			new ( jest.fn() )(),
			new ( jest.fn() )(),
		);
		expect( appMock.use ).toHaveBeenCalledWith( InLanguagePlugin, languageInfoRepo );
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
