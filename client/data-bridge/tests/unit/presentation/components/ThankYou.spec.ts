import ThankYou from '@/presentation/components/ThankYou.vue';
import { shallowMount } from '@vue/test-utils';
import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';

describe( 'ThankYou', () => {
	it( 'uses the expected messages to communicate with the user', () => {
		const wrapper = shallowMount( ThankYou, {
			propsData: { repoLink: 'https://example.com' },
		} );

		expect( wrapper.find( '.wb-db-thankyou__head' ).text() )
			.toBe( '⧼wikibase-client-data-bridge-thank-you-head⧽' );
		expect( wrapper.find( '.wb-db-thankyou__body' ).text() )
			.toBe( '⧼wikibase-client-data-bridge-thank-you-edit-reference-on-repo-body⧽' );
		expect( wrapper.findComponent( EventEmittingButton ).attributes( 'message' ) )
			.toBe( '⧼wikibase-client-data-bridge-thank-you-edit-reference-on-repo-button⧽' );
	} );

	it( 'uses the repoLink property for the primary CTA', () => {
		const repoLink = 'https://example.com';
		const wrapper = shallowMount( ThankYou, {
			propsData: { repoLink },
		} );
		const button = wrapper.findComponent( EventEmittingButton );

		expect( button.attributes( 'href' ) ).toBe( repoLink );
	} );

	it( 'propagates clicks on the primary CTA as custom event', () => {
		const wrapper = shallowMount( ThankYou, {
			propsData: { repoLink: 'https://example.com' },
		} );
		wrapper.findComponent( EventEmittingButton ).vm.$emit( 'click' );

		expect( wrapper.emitted( 'opened-reference-edit-on-repo' ) ).toHaveLength( 1 );
	} );
} );
