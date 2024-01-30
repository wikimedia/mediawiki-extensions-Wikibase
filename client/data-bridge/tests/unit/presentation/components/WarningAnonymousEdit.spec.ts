import MessageKeys from '@/definitions/MessageKeys';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import WarningAnonymousEdit from '@/presentation/components/WarningAnonymousEdit.vue';
import { shallowMount, config } from '@vue/test-utils';
import { createTestStore } from '../../../util/store';
import { ErrorTypes } from '../../../../src/definitions/ApplicationError';

beforeAll( () => {
	config.global.renderStubDefaultSlot = true;
} );

afterAll( () => {
	config.global.renderStubDefaultSlot = false;
} );

describe( 'WarningAnonymousEdit', () => {
	it( 'matches the snapshot with tempUserDisabled', () => {
		const store = createTestStore( {
			state: {
				applicationErrors: [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ],
				tempUserEnabled: false,
			},
		} );
		const $messages = {
			KEYS: MessageKeys,
			get: jest.fn().mockImplementation( ( key: keyof MessageKeys ) => key.valueOf() ),
			getText: jest.fn().mockReturnValue( "Some 'text" ),
		};
		const wrapper = shallowMount( WarningAnonymousEdit, {
			propsData: {
				loginUrl: 'https://data-bridge.test/Login',
			},
			global: {
				plugins: [ store ],
				mocks: { $messages },
			},
		} );

		expect( wrapper.element ).toMatchSnapshot( 'WarningAnonymousEdit-TempUserDisabled.spec.ts' );
	} );

	it( 'matches the snapshot with tempUserEnabled', () => {
		const store = createTestStore( {
			state: {
				applicationErrors: [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ],
				tempUserEnabled: true,
			},
		} );
		const $messages = {
			KEYS: MessageKeys,
			get: jest.fn().mockImplementation( ( key: keyof MessageKeys ) => key.valueOf() ),
			getText: jest.fn().mockReturnValue( "Some 'text" ),
		};
		const wrapper = shallowMount( WarningAnonymousEdit, {
			propsData: {
				loginUrl: 'https://data-bridge.test/Login',
			},
			global: {
				mocks: { $messages },
				plugins: [ store ],
			},
		} );

		expect( wrapper.element ).toMatchSnapshot( 'WarningAnonymousEdit-TempUserEnabled.spec.ts' );
	} );

	it( 'maps proceed button click to proceed event', async () => {
		const store = createTestStore( {
			state: {
				applicationErrors: [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ],
				tempUserEnabled: true,
			},
		} );
		const wrapper = shallowMount( WarningAnonymousEdit, {
			propsData: {
				loginUrl: 'https://data-bridge.test/Login',
			},
			global: {
				plugins: [ store ],
			},
		} );

		wrapper.findComponent( EventEmittingButton ).vm.$emit( 'click' );
		expect( wrapper.emitted( 'proceed' ) ).toHaveLength( 1 );
	} );
} );
