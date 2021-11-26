import { launch } from '@/main';
import Vue from 'vue';
import App from '@/presentation/App.vue';
import { EventEmitter } from 'events';
import { appEvents } from '@/events';
import newMockServiceContainer from './services/newMockServiceContainer';

const mockApp = {
	$mount: jest.fn(),
	$on: jest.fn(),
};
mockApp.$mount.mockImplementation( () => mockApp );
jest.mock( '@/presentation/App.vue', () => {
	return jest.fn().mockImplementation( () => mockApp );
} );

const mockEmitter = {
	on: jest.fn(),
};
jest.mock( 'events', () => ( {
	__esModule: true,
	EventEmitter: jest.fn(),
} ) );
( EventEmitter as unknown as jest.Mock ).mockImplementation( () => mockEmitter );

jest.mock( 'vue', () => {
	return {
		directive: jest.fn(),
		use: jest.fn(),
		config: {
			productionTip: true,
		},
	};
} );

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

		launch( appConfiguration, appInformation as any, services as any );

		expect( mockExtendVueEnvironment ).toHaveBeenCalledTimes( 1 );
		expect( mockExtendVueEnvironment.mock.calls[ 0 ][ 0 ] ).toBe( languageInfoRepository );
		expect( mockExtendVueEnvironment.mock.calls[ 0 ][ 1 ] ).toBe( messagesRepository );
		expect( mockExtendVueEnvironment.mock.calls[ 0 ][ 2 ] ).toBe( appInformation.client );
		expect( Vue.config.productionTip ).toBe( false );
	} );

	it( 'builds app', () => {
		const services = newMockServiceContainer( {} );

		const emitter = launch( appConfiguration, appInformation as any, services as any );

		expect( emitter ).toBe( mockEmitter );
		expect( mockCreateStore ).toHaveBeenCalledWith( services );
		expect( store.dispatch ).toHaveBeenCalledWith( 'initBridge', appInformation );
		expect( App ).toHaveBeenCalledWith( { store, propsData: { emitter: mockEmitter } } );
		expect( mockApp.$mount ).toHaveBeenCalledWith( appConfiguration.containerSelector );
		expect( mockEmitter.on ).toHaveBeenCalledTimes( 1 );
		expect( mockEmitter.on.mock.calls[ 0 ][ 0 ] ).toBe( appEvents.relaunch );
	} );

} );
