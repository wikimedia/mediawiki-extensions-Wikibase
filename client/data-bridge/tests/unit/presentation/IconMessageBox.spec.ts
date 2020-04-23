import IconMessageBox from '@/presentation/components/IconMessageBox.vue';
import { shallowMount } from '@vue/test-utils';

describe( 'IconMessageBox', () => {
	it( 'gets content through the default slot', () => {
		const message = 'some message';
		const wrapper = shallowMount( IconMessageBox, {
			slots: { default: message },
			propsData: { type: 'notice' },
		} );
		expect( wrapper.text() ).toBe( message );
	} );

	it( 'sets its class based on its `type` prop', () => {
		const wrapper = shallowMount( IconMessageBox, {
			propsData: { type: 'notice' },
		} );
		expect( wrapper.classes() ).toContain( 'wb-ui-icon-message-box--notice' );
	} );

	it( 'throws for unknown type', () => {
		expect( () => shallowMount( IconMessageBox, {
			propsData: { type: 'potato' },
		} ) ).toThrow();
	} );

	it( 'sets the component as block by default', () => {
		const wrapper = shallowMount( IconMessageBox, {
			propsData: { type: 'notice' },
		} );

		expect( wrapper.classes() ).toContain( 'wb-ui-icon-message-box--block' );
	} );

	it( 'not sets the component to block, if inline is set to true', () => {
		const wrapper = shallowMount( IconMessageBox, {
			propsData: {
				type: 'notice',
				inline: true,
			},
		} );

		expect( wrapper.classes() ).not.toContain( 'wb-ui-icon-message-box--block' );
	} );
} );
