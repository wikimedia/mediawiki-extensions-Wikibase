import { launch } from '@/main';
import { EventEmitter } from 'events';
import { appEvents } from '@/events';
import newMockServiceContainer from './services/newMockServiceContainer';

jest.mock( '@/presentation/App.vue', () => {
	return jest.fn().mockImplementation( () => ( {} ) );
} );

const mockEmitter = {
	on: jest.fn(),
};
jest.mock( 'events', () => ( {
	__esModule: true,
	EventEmitter: jest.fn(),
} ) );
( EventEmitter as unknown as jest.Mock ).mockImplementation( () => mockEmitter );

const mockVue = {
	mount: jest.fn(),
	use: jest.fn(),
};

const store = {
	dispatch: jest.fn(),
	subscribe: jest.fn(),
};
const mockCreateStore = jest.fn( ( _x: any ) => store );
jest.mock( '@/store', () => ( {
	__esModule: true,
	createStore: ( services: any ) => mockCreateStore( services ),
} ) );

const mockExtendVueEnvironment = jest.fn();
jest.mock( '@/presentation/extendVueEnvironment', () => ( {
	__esModule: true,
	default: ( ...args: readonly any[] ) => mockExtendVueEnvironment( ...args ),
} ) );

const messagesRepository = {};
const appInformation = {
	client: {
		usePublish: true,
	},
	propertyId: 'P42',
};
const appConfiguration = {
	containerSelector: '',
};

describe( 'launch', () => {

	it( 'modifies Vue', () => {
		const languageInfoRepository = {};

		const services = newMockServiceContainer( {
			languageInfoRepository,
			messagesRepository,
		} );

		const createMwApp = jest.fn().mockImplementation( () => mockVue );

		launch( createMwApp, appConfiguration, appInformation as any, services as any );

		expect( mockExtendVueEnvironment ).toHaveBeenCalledTimes( 1 );
		expect( mockExtendVueEnvironment.mock.calls[ 0 ][ 0 ] ).toBe( mockVue );
		expect( mockExtendVueEnvironment.mock.calls[ 0 ][ 1 ] ).toBe( languageInfoRepository );
		expect( mockExtendVueEnvironment.mock.calls[ 0 ][ 2 ] ).toBe( messagesRepository );
	} );

	it( 'builds app', () => {
		const services = newMockServiceContainer( {} );
		const createMwApp = jest.fn().mockImplementation( () => mockVue );

		const emitter = launch( createMwApp, appConfiguration, appInformation as any, services as any );

		expect( emitter ).toBe( mockEmitter );
		expect( mockCreateStore ).toHaveBeenCalledWith( services );
		expect( store.dispatch ).toHaveBeenCalledWith( 'initBridge', appInformation );
		expect( mockVue.mount ).toHaveBeenCalledWith( appConfiguration.containerSelector );
		expect( mockEmitter.on ).toHaveBeenCalledTimes( 1 );
		expect( mockEmitter.on.mock.calls[ 0 ][ 0 ] ).toBe( appEvents.relaunch );
	} );

} );
