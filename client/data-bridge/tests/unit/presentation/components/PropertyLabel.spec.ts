import { shallowMount } from '@vue/test-utils';
import inlanguage from '@/presentation/directives/inlanguage';
import PropertyLabel from '@/presentation/components/PropertyLabel.vue';
import Bcp47Language from '@/datamodel/Bcp47Language';
import Term from '@/datamodel/Term';

describe( 'PropertyLabel', () => {
	const enTerm: Term = {
		value: 'taxon name',
		language: 'en',
	};

	const directives = {
		inlanguage: inlanguage( {
			resolve( languageCode: string ): Bcp47Language {
				switch ( languageCode ) {
					case 'en':
						return { code: 'en', directionality: 'ltr' };
					case 'he':
						return { code: 'he', directionality: 'rtl' };
					default:
						return { code: languageCode, directionality: 'auto' };
				}
			},
		} ),
	};

	it( 'has a label', () => {
		const wrapper = shallowMount( PropertyLabel, {
			propsData: {
				term: enTerm,
				htmlFor: 'fake-id',
			},
			directives,
		} );

		expect( wrapper.find( 'label' ).text() ).toBe( enTerm.value );
	} );

	it( 'sets the for attribute', () => {
		const fakeId = `fake-id-${Math.random() * 1000}`;
		const wrapper = shallowMount( PropertyLabel, {
			propsData: {
				term: enTerm,
				htmlFor: fakeId,
			},
			directives,
		} );

		expect( wrapper.find( 'label' ).attributes( 'for' ) ).toBe( fakeId );
	} );

	it( 'sets the lang and dir attributes in English', () => {
		const wrapper = shallowMount( PropertyLabel, {
			propsData: {
				term: enTerm,
				htmlFor: 'fake-id',
			},
			directives,
		} );

		const label = wrapper.find( 'label' );
		expect( label.attributes( 'lang' ) ).toBe( 'en' );
		expect( label.attributes( 'dir' ) ).toBe( 'ltr' );
	} );

	it( 'sets the lang and dir attributes in Hebrew', () => {
		const wrapper = shallowMount( PropertyLabel, {
			propsData: {
				term: {
					value: 'שם מדעי',
					language: 'he',
				},
				htmlFor: 'fake-id',
			},
			directives,
		} );

		const label = wrapper.find( 'label' );
		expect( label.attributes( 'lang' ) ).toBe( 'he' );
		expect( label.attributes( 'dir' ) ).toBe( 'rtl' );
	} );
} );
