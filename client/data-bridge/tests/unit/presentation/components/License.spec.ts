import { shallowMount } from '@vue/test-utils';
import MessageKeys from '@/definitions/MessageKeys';
import License from '@/presentation/components/License.vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';

describe( 'License component', () => {

	const $messages = {
		KEYS: MessageKeys,
		get: jest.fn( ( key: string ) => `⧼${key}⧽` ),
		getText: jest.fn( ( key: string ) => `⧼${key}⧽` ),
	};

	it( 'bubbles the button\'s click event as close event', () => {
		const wrapper = shallowMount( License );
		wrapper.find( EventEmittingButton ).vm.$emit( 'click' );
		expect( wrapper.emitted( 'close' ) ).toHaveLength( 1 );
	} );

	it( 'mounts a button with the correct props', () => {
		const wrapper = shallowMount( License );

		expect( wrapper.find( EventEmittingButton ).props( 'size' ) ).toBe( 'M' );
		expect( wrapper.find( EventEmittingButton ).props( 'type' ) ).toBe( 'close' );
	} );

	it( 'calls license body with correct parameters', () => {
		const $bridgeConfig = {
			usePublish: false,
			termsOfUseUrl: 'termsOfUseUrl',
			dataRightsUrl: 'dataRightsUrl',
			dataRightsText: 'dataRightsText',
		};
		shallowMount( License, {
			mocks: { $messages, $bridgeConfig },
		} );

		const messageKeys = $messages.get.mock.calls.reduce( ( acc: Record<string, string[]>, call: string[] ) => {
			acc[ call.shift()! ] = call;
			return acc;
		}, {} );
		expect( messageKeys[ MessageKeys.LICENSE_BODY ] ).toEqual( [
			MessageKeys.SAVE_CHANGES,
			$bridgeConfig.termsOfUseUrl,
			$bridgeConfig.dataRightsUrl,
			$bridgeConfig.dataRightsText,
		] );
	} );

} );
