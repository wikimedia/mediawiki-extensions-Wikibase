import { shallowMount } from '@vue/test-utils';
import RadioInput from '@/presentation/components/RadioInput.vue';

function shallowMountWithPropsAndSlots( props = {}, slots = {} ): any {
	return shallowMount( RadioInput, {
		propsData: {
			name: 'radio input name',
			htmlValue: 'the value',
			...props,
		},
		slots: {
			label: 'label text',
			description: 'description text',
			...slots,
		},
	} );
}

describe( 'RadioInput', () => {
	it( 'shows content of label slot', () => {
		const message = 'label text';
		const wrapper = shallowMountWithPropsAndSlots(
			{},
			{
				label: `<strong>${message}</strong>`,
			},
		);
		expect( wrapper.find( 'strong' ).text() ).toBe( message );
	} );

	it( 'shows content of description slot', () => {
		const message = 'description text';
		const wrapper = shallowMountWithPropsAndSlots(
			{},
			{
				description: `<strong>${message}</strong>`,
			},
		);
		expect( wrapper.find( 'strong' ).text() ).toBe( message );
	} );

	it( 'is not pre-selected if value does not match htmlValue', () => {
		const wrapper = shallowMountWithPropsAndSlots(
			{
				htmlValue: 'foo',
				value: 'bar',
			},
		);
		expect( wrapper.find( 'input:checked' ).exists() ).toBe( false );
	} );

	it( 'is pre-selected if value matches htmlValue', () => {
		const wrapper = shallowMountWithPropsAndSlots(
			{
				htmlValue: 'foo',
				value: 'foo',
			},
		);
		expect( wrapper.find( 'input:checked' ).exists() ).toBe( true );
	} );

	it( 'emits value as input event if there is a change event on the radio input', () => {
		const radioInputValue = 'some value';
		const wrapper = shallowMountWithPropsAndSlots(
			{
				htmlValue: radioInputValue,
			},
		);
		wrapper.find( 'input[type=radio]' ).element.checked = true;
		wrapper.find( 'input[type=radio]' ).trigger( 'change' );
		expect( wrapper.emitted( 'input' ) ).toStrictEqual( [ [ radioInputValue ] ] );
	} );
} );
