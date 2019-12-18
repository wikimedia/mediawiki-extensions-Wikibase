import PropertyLabel from '@/presentation/components/PropertyLabel.vue';
import StringDataValue from '@/presentation/components/StringDataValue.vue';
import { ResizingTextField } from '@wmde/wikibase-vuejs-components';
import { shallowMount } from '@vue/test-utils';

describe( 'StringDataValue', () => {
	describe( 'label and editfield', () => {
		it( 'passes the label down', () => {
			const label = { value: 'P123', language: 'zxx' };
			const wrapper = shallowMount( StringDataValue, {
				propsData: {
					label,
					dataValue: null,
					setDataValue: () => {},
				},
			} );

			expect( wrapper.find( PropertyLabel ).props( 'term' ) ).toBe( label );
		} );

		it( 'passes the DataValue down', () => {
			const dataValue = { type: 'string', value: 'Töfften' };
			const wrapper = shallowMount( StringDataValue, {
				propsData: {
					label: '',
					dataValue,
					setDataValue: () => {},
				},
			} );

			expect( wrapper.find( ResizingTextField ).props( 'value' ) ).toBe( dataValue.value );
		} );

		it( 'triggers the setter with the new value when it is edited', () => {
			const dataValue = { type: 'string', value: 'Töfften' };
			const mockSetter = jest.fn();
			const wrapper = shallowMount( StringDataValue, {
				propsData: {
					label: '',
					dataValue,
					setDataValue: mockSetter,
				},
			} );
			const testString = 'newString';

			wrapper.find( ResizingTextField ).vm.$emit( 'input', testString );

			expect( mockSetter ).toHaveBeenCalledWith( { type: 'string', value: testString } );
		} );

		it( 'binds label and editField', () => {
			const wrapper = shallowMount( StringDataValue, {
				propsData: {
					label: '',
					dataValue: null,
					setDataValue: () => {},
				},
			} );

			expect(
				wrapper.find( PropertyLabel ).props( 'htmlFor' ),
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
					setDataValue: () => {},
				},
			} );

		expect( wrapper.find( ResizingTextField ).attributes( 'placeholder' ) )
			.toBe( placeholder );
	} );

	describe( 'maxlength', () => {
		it( 'passes through if set', () => {
			const maxlength = 12345;
			const wrapper = shallowMount( StringDataValue, {
				propsData: {
					label: null,
					dataValue: null,
					setDataValue: () => {},
					maxlength,
				},
			} );

			expect(
				wrapper.find( ResizingTextField ).attributes( 'maxlength' ),
			).toBe( maxlength.toString() );
		} );

		it( 'is unset by default', () => {
			const wrapper = shallowMount( StringDataValue, {
				propsData: {
					label: null,
					dataValue: null,
					setDataValue: () => {},
				},
			} );

			expect( wrapper.find( ResizingTextField ).attributes( 'maxlength' ) )
				.toBeUndefined();
		} );
	} );
} );
