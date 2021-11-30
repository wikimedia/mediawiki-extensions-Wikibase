import MessageKeys from '@/definitions/MessageKeys';
import BailoutActions from '@/presentation/components/BailoutActions.vue';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import { shallowMount } from '@vue/test-utils';

describe( 'BailoutActions', () => {
	const originalHref = 'https://repo.example.com/wiki/Item:Q42?uselang=en';
	const pageTitle = 'Client_title';

	const $messages = {
		KEYS: MessageKeys,
		get: jest.fn( ( key: string ) => `⧼${key}⧽` ),
		getText: jest.fn( ( key: string ) => `⧼${key}⧽` ),
	};
	const $clientRouter = {
		getPageUrl: jest.fn(),
	};

	it( 'only uses bailout messages', () => {
		shallowMount( BailoutActions, {
			propsData: { originalHref, pageTitle },
			global: {
				mocks: { $messages, $clientRouter },
			},
		} );

		const messageKeys = $messages.getText.mock.calls.map( ( call ) => call[ 0 ] );
		messageKeys.forEach( ( messageKey ) => {
			expect( messageKey ).toMatch( /^wikibase-client-data-bridge-bailout-/ );
		} );
	} );

	it( 'calls the client router correctly', () => {
		shallowMount( BailoutActions, {
			propsData: { originalHref, pageTitle },
			global: {
				mocks: { $messages, $clientRouter },
			},
		} );

		expect( $clientRouter.getPageUrl ).toHaveBeenCalledWith( pageTitle, { action: 'edit' } );
	} );

	it( 'passes the original href into the button', () => {
		const wrapper = shallowMount( BailoutActions, {
			propsData: { originalHref, pageTitle },
			global: {
				mocks: { $messages, $clientRouter },
			},
		} );

		expect( wrapper.findComponent( EventEmittingButton ).props( 'href' ) ).toBe( originalHref );
	} );
} );
