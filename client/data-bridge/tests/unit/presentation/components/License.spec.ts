import { shallowMount } from '@vue/test-utils';
import MessageKeys from '@/definitions/MessageKeys';
import License from '@/presentation/components/License.vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import { createTestStore } from '../../../util/store';
import { BridgeConfig } from '@/store/Application';

describe( 'License component', () => {
	const store = createTestStore( {
		getters: {
			get config() {
				return {
					usePublish: false,
					termsOfUseUrl: 'termsOfUseUrl',
					dataRightsUrl: 'dataRightsUrl',
					dataRightsText: 'dataRightsText',
				} as BridgeConfig;
			},
		},
	} );

	it( 'bubbles the button\'s click event as close event', () => {
		const wrapper = shallowMount(
			License,
			{ global: { plugins: [ store ] } },
		);
		wrapper.findComponent( EventEmittingButton ).vm.$emit( 'click' );
		expect( wrapper.emitted( 'close' ) ).toHaveLength( 1 );
	} );

	it( 'mounts a button with the correct props', () => {
		const wrapper = shallowMount(
			License,
			{ global: { plugins: [ store ] } },
		);

		expect( wrapper.findComponent( EventEmittingButton ).props( 'size' ) ).toBe( 'M' );
		expect( wrapper.findComponent( EventEmittingButton ).props( 'type' ) ).toBe( 'close' );
	} );

	it( 'calls license body with correct parameters', () => {
		const bridgeConfig = {
			usePublish: false,
			termsOfUseUrl: 'termsOfUseUrl',
			dataRightsUrl: 'dataRightsUrl',
			dataRightsText: 'dataRightsText',
		};
		const localStore = createTestStore( {
			getters: {
				get config() {
					return bridgeConfig as BridgeConfig;
				},
			},
		} );
		const $messages = {
			KEYS: MessageKeys,
			get: jest.fn( ( key: string ) => `⧼${key}⧽` ),
			getText: jest.fn( ( key: string ) => `⧼${key}⧽` ),
		};
		shallowMount( License, {
			global: {
				mocks: { $messages },
				plugins: [ localStore ],
			},
		} );

		const messageKeys = $messages.get.mock.calls.reduce( ( acc: Record<string, string[]>, call: string[] ) => {
			acc[ call.shift()! ] = call;
			return acc;
		}, {} );
		expect( messageKeys[ MessageKeys.LICENSE_BODY ] ).toEqual( [
			MessageKeys.SAVE_CHANGES,
			bridgeConfig.termsOfUseUrl,
			bridgeConfig.dataRightsUrl,
			bridgeConfig.dataRightsText,
		] );
	} );

} );
