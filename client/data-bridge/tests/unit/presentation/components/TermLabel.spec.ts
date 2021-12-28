import Term from '@/datamodel/Term';
import TermLabel from '@/presentation/components/TermLabel.vue';
import { shallowMount } from '@vue/test-utils';
import { testInLanguage } from '../../../util/language';

describe( 'TermLabel', () => {
	const enTerm: Term = {
		value: 'taxon name',
		language: 'en',
	};

	it( 'uses the term value as the text', () => {
		const wrapper = shallowMount( TermLabel, {
			propsData: { term: enTerm, inLanguage: testInLanguage },
		} );

		expect( wrapper.text() ).toBe( enTerm.value );
	} );

	it( 'sets the lang and dir attributes in English', () => {
		const wrapper = shallowMount( TermLabel, {
			propsData: { term: enTerm, inLanguage: testInLanguage },
		} );

		expect( wrapper.attributes( 'lang' ) ).toBe( 'en' );
		expect( wrapper.attributes( 'dir' ) ).toBe( 'ltr' );
	} );

	it( 'sets the lang and dir attributes in Hebrew', () => {
		const wrapper = shallowMount( TermLabel, {
			propsData: {
				term: {
					value: 'שם מדעי',
					language: 'he',
				},
				inLanguage: testInLanguage,
			},
		} );

		expect( wrapper.attributes( 'lang' ) ).toBe( 'he' );
		expect( wrapper.attributes( 'dir' ) ).toBe( 'rtl' );
	} );
} );
