import EventEmittingButton from '@/presentation/components/EventEmittingButton.vue';
import { shallowMount } from '@vue/test-utils';

function shallowMountWithProps( props = {} ): any {
	return shallowMount( EventEmittingButton, {
		propsData: {
			type: 'primaryProgressive',
			message: 'click me',
			...props,
		},
	} );
}
describe( 'EventEmittingButton', () => {
	it( 'emits an event on click', () => {
		const wrapper = shallowMountWithProps();
		wrapper.find( 'a' ).trigger( 'click' );
		const clickEvent = wrapper.emitted( 'click' );
		expect( clickEvent ).toBeTruthy();
		const originalEvent: MouseEvent = clickEvent[ 0 ][ 0 ];
		expect( originalEvent ).toBeInstanceOf( MouseEvent );
		expect( originalEvent.defaultPrevented ).toBeTruthy();
	} );

	it( 'can be configured not to prevent default (href) event', () => {
		const wrapper = shallowMountWithProps( {
			preventDefault: false,
		} );
		wrapper.find( 'a' ).trigger( 'click' );
		const clickEvent = wrapper.emitted( 'click' );
		expect( clickEvent ).toBeTruthy();
		const originalEvent: MouseEvent = clickEvent[ 0 ][ 0 ];
		expect( originalEvent ).toBeInstanceOf( MouseEvent );
		expect( originalEvent.defaultPrevented ).toBeFalsy();
	} );

	it( 'can have a link target', () => {
		const href = '/edit/Q1';
		const wrapper = shallowMountWithProps( { href } );
		expect( wrapper.find( 'a' ).attributes( 'href' ) ).toBe( href );
	} );

	it( 'has a default href', () => {
		const wrapper = shallowMountWithProps();
		expect( wrapper.find( 'a' ).attributes( 'href' ) ).toBe( '#' );
	} );

	it( 'sets its class based on its `type` prop', () => {
		const wrapper = shallowMountWithProps( { type: 'primaryProgressive' } );
		expect( wrapper.classes() ).toContain( 'wb-ui-event-emitting-button--primaryProgressive' );
	} );

	describe( 'squary modifier ', () => {
		it( 'is not added by default', () => {
			const wrapper = shallowMountWithProps( { type: 'primaryProgressive' } );
			expect( wrapper.classes() ).not.toContain( 'wb-ui-event-emitting-button--squary' );
		} );

		it( 'is added if `squary` prop is true', () => {
			const wrapper = shallowMountWithProps( { type: 'primaryProgressive', squary: true } );
			expect( wrapper.classes() ).toContain( 'wb-ui-event-emitting-button--squary' );
		} );
	} );

	it( 'throws for unknown type', () => {
		expect( () => shallowMountWithProps( { type: 'potato' } ) ).toThrow();
	} );

	it( 'shows a message in its title attribute and text content', () => {
		const message = 'click me';
		const wrapper = shallowMountWithProps( { message } );
		expect( wrapper.find( 'a' ).text() ).toBe( message );
		expect( wrapper.find( 'a' ).attributes( 'title' ) ).toBe( message );
	} );
} );
