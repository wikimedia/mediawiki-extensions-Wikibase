import StringDataValue from '@/presentation/components/StringDataValue.vue';
import ResizingTextField from '@/presentation/components/ResizingTextField.vue';
import { shallowMount } from '@vue/test-utils';

describe( 'StringDataValue', () => {
	describe( 'label and editfield', () => {
		it( 'has a label', () => {
			const label = 'P123';
			const wrapper = shallowMount( StringDataValue, {
				propsData: {
					label,
					dataValue: null,
				},
			} );

			expect( wrapper.find( '.wb-db-stringValue__label' ).text() ).toBe( label );
		} );

		it( 'passes the DataValue down', () => {
			const dataValue = { type: 'string', value: 'Töfften' };
			const wrapper = shallowMount( StringDataValue, {
				propsData: {
					label: '',
					dataValue,
				},
			} );

			expect( wrapper.find( ResizingTextField ).props( 'value' ) ).toBe( dataValue.value );
		} );

		/* it( `triggers ${STATEMENT_MAINSNAK_STRING_VALUE_EDIT} when it is edited`, () => {

		} );*/

		it( 'binds label and editField', () => {
			const wrapper = shallowMount( StringDataValue, {
				propsData: {
					label: '',
					dataValue: null,
				},
			} );

			expect(
				wrapper.find( '.wb-db-stringValue__label' ).attributes( 'for' ),
			).toBe(
				wrapper.find( ResizingTextField ).attributes( 'id' ),
			);
		} );
	} );

	it( 'passes a placeholder down', () => {
		const placeholder = 'a placeholder',
			wrapper = shallowMount( StringDataValue, {
				propsData: {
					label: '',
					dataValue: null,
					placeholder,
				},
			} );

		expect( wrapper.find( ResizingTextField ).attributes( 'placeholder' ) )
			.toBe( placeholder );
	} );

	/* it( 'passes a maxlength down', () => {

	} );*/
} );
