import { shallowMount } from '@vue/test-utils';
import PropertyLabel from '@/presentation/components/PropertyLabel.vue';
import TermLabel from '@/presentation/components/TermLabel.vue';
import Term from '@/datamodel/Term';

describe( 'PropertyLabel', () => {
	const enTerm: Term = {
		value: 'taxon name',
		language: 'en',
	};

	it( 'has a label', () => {
		const wrapper = shallowMount( PropertyLabel, {
			propsData: {
				term: enTerm,
				htmlFor: 'fake-id',
			},
		} );

		expect( wrapper.find( 'label' ).exists() ).toBe( true );
	} );

	it( 'mounts a TermLabel with the term inside the label', () => {
		const wrapper = shallowMount( PropertyLabel, {
			propsData: {
				term: enTerm,
				htmlFor: 'fake-id',
			},
		} );

		const termLabelWrapper = wrapper.find( 'label' ).findComponent( TermLabel );
		expect( termLabelWrapper.exists() ).toBe( true );
		expect( termLabelWrapper.props( 'term' ) ).toStrictEqual( enTerm );
	} );

	it( 'sets the for attribute', () => {
		const fakeId = `fake-id-${Math.random() * 1000}`;
		const wrapper = shallowMount( PropertyLabel, {
			propsData: {
				term: enTerm,
				htmlFor: fakeId,
			},
		} );

		expect( wrapper.find( 'label' ).attributes( 'for' ) ).toBe( fakeId );
	} );
} );
