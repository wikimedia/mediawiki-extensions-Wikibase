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
const propertyDataTypeRepository = {
	getDataType: jest.fn().mockResolvedValue( 'string' ),
};
const bridgeTracker = {
	trackPropertyDatatype: jest.fn(),
};
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
		const languageRepo = {};

		const services = {
			getLanguageInfoRepository: () => languageRepo,
			getMessagesRepository: () => messagesRepository,
			getPropertyDatatypeRepository: () => propertyDataTypeRepository,
			getTracker: () => bridgeTracker,
		};

		launch( appConfiguration, appInformation as any, services as any );

		expect( extendVueEnvironment ).toHaveBeenCalledTimes( 1 );
		expect( extendVueEnvironment.mock.calls[ 0 ][ 0 ] ).toBe( languageRepo );
		expect( extendVueEnvironment.mock.calls[ 0 ][ 1 ] ).toBe( messagesRepository );
		expect( extendVueEnvironment.mock.calls[ 0 ][ 2 ] ).toBe( appInformation.client );
		expect( Vue.config.productionTip ).toBe( false );
	} );

	it( 'builds app', () => {
		const languageRepo = {};

		const services = {
			getLanguageInfoRepository: () => languageRepo,
			getMessagesRepository: () => messagesRepository,
			getPropertyDatatypeRepository: () => propertyDataTypeRepository,
			getTracker: () => bridgeTracker,
		};

		const emitter = launch( appConfiguration, appInformation as any, services as any );

		expect( emitter ).toBe( mockEmitter );
		expect( mockCreateStore ).toHaveBeenCalledWith( services );
		expect( store.dispatch ).toHaveBeenCalledWith( BRIDGE_INIT, appInformation );
		expect( App ).toHaveBeenCalledWith( { store } );
		expect( mockApp.$mount ).toHaveBeenCalledWith( appConfiguration.containerSelector );
		expect( mockRepeater ).toHaveBeenCalledWith(
			mockApp,
			mockEmitter,
			Object.values( Events ),
		);
	} );

	it( 'tracks opening of the bridge with the expected data type', () => {
		const languageRepo = {};
		const dataType = 'string';
		const getDataTypePromise = Promise.resolve( dataType );
		const propertyDataTypeRepository = {
			getDataType: jest.fn().mockReturnValue( getDataTypePromise ),
		};
		const services = {
			getLanguageInfoRepository: () => languageRepo,
			getMessagesRepository: () => messagesRepository,
			getPropertyDatatypeRepository: () => propertyDataTypeRepository,
			getTracker: () => bridgeTracker,
		};

		launch( appConfiguration, appInformation as any, services as any );

		return getDataTypePromise.then( () => {
			expect( propertyDataTypeRepository.getDataType ).toHaveBeenCalledWith( appInformation.propertyId );
			expect( bridgeTracker.trackPropertyDatatype ).toHaveBeenCalledWith( dataType );
		} );
	} );
} );
