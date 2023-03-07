import MessageKeys from '@/definitions/MessageKeys';
import ErrorSavingAssertUser from '@/presentation/components/ErrorSavingAssertUser.vue';
import {
	config,
	shallowMount,
} from '@vue/test-utils';
import { createTestStore } from '../../../util/store';
import { BridgeConfig } from '@/store/Application';
import { ComponentOptions, nextTick } from 'vue';

beforeAll( () => {
	config.global.renderStubDefaultSlot = true;
} );

afterAll( () => {
	config.global.renderStubDefaultSlot = false;
} );

describe( 'ErrorSavingAssertUser', () => {
	const stopAssertingUserWhenSaving = jest.fn();
	const retrySave = jest.fn();
	const goBackFromErrorToReady = jest.fn();
	const store = createTestStore( {
		actions: {
			stopAssertingUserWhenSaving,
			retrySave,
			goBackFromErrorToReady,
		},
		state: {
			config: { usePublish: false } as BridgeConfig,
		},
	} );

	it( 'matches the snapshot', () => {
		const $messages = {
			KEYS: MessageKeys,
			getText: jest.fn().mockReturnValue( "Some 'text" ),
		};

		const wrapper = shallowMount( ErrorSavingAssertUser, {
			propsData: {
				loginUrl: 'https://data-bridge.test/Login',
			},
			global: {
				mocks: { $messages },
				plugins: [ store ],
			},
		} );

		expect( wrapper.element ).toMatchSnapshot();
	} );

	it( 'dispatches stopAssertingUserWhenSaving and retrySave when publish without logging in is clicked', async () => {
		const $messages = {
			KEYS: MessageKeys,
			getText: jest.fn().mockReturnValue( "Some 'text" ),
		};

		const wrapper = shallowMount( ErrorSavingAssertUser, {
			propsData: {
				loginUrl: 'https://data-bridge.test/Login',
			},
			global: {
				mocks: { $messages },
				plugins: [ store ],
			},
		} );

		const button = wrapper.findComponent<ComponentOptions>( '.wb-db-error-saving-assertuser__proceed' );
		await button.vm.$emit( 'click' );
		await nextTick();

		expect( stopAssertingUserWhenSaving ).toHaveBeenCalledTimes( 1 );
		expect( retrySave ).toHaveBeenCalledTimes( 1 );
	} );

	// from our point of view, login/app behave identically; difference is tested in browser tests
	it.each( [
		[ 'login' ],
		[ 'back' ],
	] )( 'goes back if the %s button is clicked', async ( buttonName: string ) => {
		const $messages = {
			KEYS: MessageKeys,
			getText: jest.fn().mockReturnValue( "Some 'text" ),
		};

		const wrapper = shallowMount( ErrorSavingAssertUser, {
			propsData: {
				loginUrl: 'https://data-bridge.test/Login',
			},
			global: {
				mocks: { $messages },
				plugins: [ store ],
			},
		} );

		const button = wrapper.findComponent<ComponentOptions>( `.wb-db-error-saving-assertuser__${buttonName}` );
		button.vm.$emit( 'click' );

		expect( goBackFromErrorToReady ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'renders the proceed button using the SAVING_ERROR_ASSERTUSER_SAVE message', () => {
		const saveMessage = 'Save without logging in';
		const messageGet = jest.fn(
			( key: string ) => {
				if ( key === MessageKeys.SAVING_ERROR_ASSERTUSER_SAVE ) {
					return saveMessage;
				}

				return '';
			},
		);

		const wrapper = shallowMount( ErrorSavingAssertUser, {
			propsData: {
				loginUrl: 'https://data-bridge.test/Login',
			},
			global: {
				mocks: {
					$bridgeConfig: { usePublish: false },
					$messages: {
						KEYS: MessageKeys,
						getText: messageGet,
					},
				},
				plugins: [ store ],
			},
		} );

		const button = wrapper.findComponent<ComponentOptions>( '.wb-db-error-saving-assertuser__proceed' );

		expect( messageGet ).toHaveBeenCalledWith( MessageKeys.SAVING_ERROR_ASSERTUSER_SAVE );
		expect( button.props( 'message' ) ).toBe( saveMessage );
	} );

	it( 'renders the proceed button using the SAVING_ERROR_ASSERTUSER_PUBLISH message', () => {
		const publishMessage = 'Publish without logging in';
		const messageGet = jest.fn(
			( key: string ) => {
				if ( key === MessageKeys.SAVING_ERROR_ASSERTUSER_PUBLISH ) {
					return publishMessage;
				}

				return '';
			},
		);
		const localStore = createTestStore( {
			actions: {
				stopAssertingUserWhenSaving,
				retrySave,
				goBackFromErrorToReady,
			},
			state: {
				config: { usePublish: true } as BridgeConfig,
			},
		} );

		const wrapper = shallowMount( ErrorSavingAssertUser, {
			propsData: {
				loginUrl: 'https://data-bridge.test/Login',
			},
			global: {
				mocks: {
					$messages: {
						KEYS: MessageKeys,
						getText: messageGet,
					},
				},
				plugins: [ localStore ],
			},
		} );

		const button = wrapper.findComponent<ComponentOptions>( '.wb-db-error-saving-assertuser__proceed' );

		expect( messageGet ).toHaveBeenCalledWith( MessageKeys.SAVING_ERROR_ASSERTUSER_PUBLISH );
		expect( button.props( 'message' ) ).toBe( publishMessage );
	} );

} );
