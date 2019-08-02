import { mount } from '@vue/test-utils';
import ResizingTextField from '@/presentation/components/ResizingTextField.vue';

describe( 'ResizingTextField', () => {

	it( 'is a textarea element', () => {
		const wrapper = mount( ResizingTextField, {} );
		expect( wrapper.element.nodeName ).toBe( 'TEXTAREA' );
	} );

	it( 'shows its value prop as the textarea value', () => {
		const value = 'hello';
		const wrapper = mount( ResizingTextField, {
			propsData: {
				value,
			},
		} );

		expect( ( wrapper.element as HTMLTextAreaElement ).value ).toBe( value );
	} );

	it( 'propagates input events to its parent', () => {
		const wrapper = mount( ResizingTextField );
		const value = 'foo';
		( wrapper.element as HTMLTextAreaElement ).value = value;
		wrapper.trigger( 'input' );
		expect( wrapper.emitted( 'input' )[ 0 ] ).toEqual( [ value ] );
	} );

	// the automatically adjusted height is hard to test in a jsdom environment

	describe( 'newline removal', () => {
		it.each( [
			[ 'bar', 'bar' ],
			[ 'loremipsumdolorsitamet', 'lorem\nipsum\ndolor\nsit\namet\n\n' ],
			[ '', '' ],
			[ 'i ama windows', 'i am\r\na windows' ],
			[ 'i ama unix', 'i am\na unix' ],
		] )(
			'propagates value as "%s" when given "%s"',
			( expected: string, given: string ) => {
				const wrapper = mount( ResizingTextField );
				const textarea = wrapper.element as HTMLTextAreaElement;
				textarea.value = given;
				wrapper.trigger( 'input' );
				expect( wrapper.emitted( 'input' )[ 0 ] ).toEqual( [ expected ] );
			},
		);
	} );
} );
