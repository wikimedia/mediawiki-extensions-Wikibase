import MessageKeys from '@/definitions/MessageKeys';
import ErrorSavingAssertUser from '@/presentation/components/ErrorSavingAssertUser.vue';
import Vuex from 'vuex';
import {
	createLocalVue,
	shallowMount,
} from '@vue/test-utils';
import { createTestStore } from '../../../util/store';

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
	} );

	const localVue = createLocalVue();
	localVue.use( Vuex );

	it( 'matches the snapshot', () => {
		const $messages = {
			KEYS: MessageKeys,
			get: jest.fn().mockReturnValue( 'Test <abbr>HTML</abbr>.' ),
		};

		const wrapper = shallowMount( ErrorSavingAssertUser, {
			propsData: {
				loginUrl: 'https://data-bridge.test/Login',
			},
			mocks: { $messages },
		} );

		expect( wrapper.element ).toMatchSnapshot();
	} );

	it( 'dispatches stopAssertingUserWhenSaving and retrySave when publish without logging in is clicked', async () => {
		const $messages = {
			KEYS: MessageKeys,
			get: jest.fn().mockReturnValue( 'Test <abbr>HTML</abbr>.' ),
		};

		const wrapper = shallowMount( ErrorSavingAssertUser, {
			propsData: {
				loginUrl: 'https://data-bridge.test/Login',
			},
			mocks: { $messages },
			store,
			localVue,
		} );
		const button = wrapper.find( '.wb-db-error-saving-assertuser__proceed' );
		button.vm.$emit( 'click' );
		await localVue.nextTick();

		expect( stopAssertingUserWhenSaving ).toHaveBeenCalledTimes( 1 );
		expect( retrySave ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'goes back if the keep editing button is clicked', async () => {
		const $messages = {
			KEYS: MessageKeys,
			get: jest.fn().mockReturnValue( 'Test <abbr>HTML</abbr>.' ),
		};

		const wrapper = shallowMount( ErrorSavingAssertUser, {
			propsData: {
				loginUrl: 'https://data-bridge.test/Login',
			},
			mocks: { $messages },
			store,
			localVue,
		} );
		const button = wrapper.find( '.wb-db-error-saving-assertuser__back' );
		button.vm.$emit( 'click' );
		await localVue.nextTick();

		expect( goBackFromErrorToReady ).toHaveBeenCalledTimes( 1 );
	} );

} );
