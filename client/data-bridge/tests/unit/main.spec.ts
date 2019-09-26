import {
	BRIDGE_INIT,
} from '@/store/actionTypes';
import { launch } from '@/main';
import Vue from 'vue';
import App from '@/presentation/App.vue';
import { EventEmitter } from 'events';
import Events from '@/events';

const mockApp = {
	$mount: jest.fn(),
};
mockApp.$mount.mockImplementation( () => mockApp );
jest.mock( '@/presentation/App.vue', () => {
	return jest.fn().mockImplementation( () => mockApp );
} );

const mockEmitter = {};
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
};
const mockCreateStore = jest.fn( ( _x: any ) => store );
jest.mock( '@/store', () => ( {
	__esModule: true,
	createStore: ( services: any ) => mockCreateStore( services ),
} ) );

const mockRepeater = jest.fn();
jest.mock( '@/events/repeater', () => ( {
	__esModule: true,
	default: ( app: any, emitter: any, events: any ) => mockRepeater( app, emitter, events ),
} ) );

const extendVueEnvironment = jest.fn();
jest.mock( '@/presentation/extendVueEnvironment', () => ( {
	__esModule: true,
	default: ( ...args: any[] ) => extendVueEnvironment( ...args ),
} ) );

const messagesRepository = {};

describe( 'launch', () => {

	it( 'modifies Vue', () => {
		const languageRepo = {};
		const services = {
			getLanguageInfoRepository: () => languageRepo,
			getMessagesRepository: () => messagesRepository,
		};
		const information = {};
		const configuration = {
			containerSelector: '',
			usePublish: true,
		};

		launch( configuration, information as any, services as any );
		expect( extendVueEnvironment ).toHaveBeenCalledTimes( 1 );
		expect( extendVueEnvironment.mock.calls[ 0 ][ 0 ] ).toBe( languageRepo );
		expect( extendVueEnvironment.mock.calls[ 0 ][ 1 ] ).toBe( messagesRepository );
		expect( extendVueEnvironment.mock.calls[ 0 ][ 2 ] ).toStrictEqual( { usePublish: configuration.usePublish } );
		expect( Vue.config.productionTip ).toBe( false );
	} );

	it( 'builds app', () => {
		const languageRepo = {};
		const services = {
			getLanguageInfoRepository: () => languageRepo,
			getMessagesRepository: () => messagesRepository,
		};

		const information = {};
		const configuration = {
			containerSelector: '',
			usePublish: false,
		};

		const emitter = launch( configuration, information as any, services as any );

		expect( emitter ).toBe( mockEmitter );
		expect( mockCreateStore ).toHaveBeenCalledWith( services );
		expect( store.dispatch ).toHaveBeenCalledWith( BRIDGE_INIT, information );
		expect( App ).toHaveBeenCalledWith( { store } );
		expect( mockApp.$mount ).toHaveBeenCalledWith( configuration.containerSelector );
		expect( mockRepeater ).toHaveBeenCalledWith(
			mockApp,
			mockEmitter,
			Object.values( Events ),
		);
	} );
} );
