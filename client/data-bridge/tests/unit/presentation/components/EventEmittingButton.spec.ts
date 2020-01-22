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

	const pressedClass = '.wb-ui-event-emitting-button--pressed';
	describe( 'if there is no href', () => {
		it( 'emits an event on click', () => {
			const wrapper = shallowMountWithProps();
			wrapper.find( 'a' ).trigger( 'click' );
			const clickEvent = wrapper.emitted( 'click' );
			expect( clickEvent ).toBeTruthy();
		} );

		it( 'emits an event on enter', () => {
			const wrapper = shallowMountWithProps();
			wrapper.find( 'a' ).trigger( 'keydown.enter' );
			const clickEvent = wrapper.emitted( 'click' );
			expect( clickEvent ).toBeTruthy();
			expect( wrapper.find( pressedClass ).exists() ).toBeTruthy();
			wrapper.find( 'a' ).trigger( 'keyup.enter' );
			expect( wrapper.find( pressedClass ).exists() ).toBeFalsy();
		} );

		it( 'emits an event on space and does not scroll down', () => {
			const wrapper = shallowMountWithProps();
			wrapper.find( 'a' ).trigger( 'keydown.space' );
			const clickEvent = wrapper.emitted( 'click' );
			expect( clickEvent ).toBeTruthy();
			const originalEvent: UIEvent = clickEvent[ 0 ][ 0 ];
			expect( originalEvent ).toBeInstanceOf( UIEvent );
			expect( originalEvent.defaultPrevented ).toBeTruthy();
			expect( wrapper.find( pressedClass ).exists() ).toBeTruthy();
			wrapper.find( 'a' ).trigger( 'keyup.space' );
			expect( wrapper.find( pressedClass ).exists() ).toBeFalsy();
		} );
	} );

	describe( 'if there is a href set and default is prevented', () => {
		it( 'emits an event on click and prevents the default', () => {
			const wrapper = shallowMountWithProps( {
				href: 'https://example.com',
			} );
			wrapper.find( 'a' ).trigger( 'click' );
			const clickEvent = wrapper.emitted( 'click' );
			expect( clickEvent ).toBeTruthy();
			const originalEvent: MouseEvent = clickEvent[ 0 ][ 0 ];
			expect( originalEvent ).toBeInstanceOf( MouseEvent );
			expect( originalEvent.defaultPrevented ).toBeTruthy();
		} );

		it( 'emits an event on enter and prevents the default', () => {
			const wrapper = shallowMountWithProps( {
				href: 'https://example.com',
			} );
			wrapper.find( 'a' ).trigger( 'keydown.enter' );
			wrapper.find( 'a' ).trigger( 'click.enter' );
			const clickEvents = wrapper.emitted( 'click' );
			expect( clickEvents ).toBeTruthy();
			expect( clickEvents ).toHaveLength( 1 );
			const originalEvent: UIEvent = clickEvents[ 0 ][ 0 ];
			expect( originalEvent ).toBeInstanceOf( UIEvent );
			expect( originalEvent.defaultPrevented ).toBeTruthy();
			expect( wrapper.find( pressedClass ).exists() ).toBeTruthy();
			wrapper.find( 'a' ).trigger( 'keyup.enter' );
			expect( wrapper.find( pressedClass ).exists() ).toBeFalsy();
		} );

		it( 'does nothing on space', () => {
			const wrapper = shallowMountWithProps( {
				href: 'https://example.com',
			} );
			wrapper.find( 'a' ).trigger( 'keydown.space' );
			expect( wrapper.emitted( 'click' ) ).toBeFalsy();
			expect( wrapper.find( pressedClass ).exists() ).toBeFalsy();
		} );
	} );

	describe( 'if there is a href set and default is not prevented', () => {
		it( 'opens the link on click', () => {
			const wrapper = shallowMountWithProps( {
				href: 'https://example.com',
				preventDefault: false,
			} );
			wrapper.find( 'a' ).trigger( 'click' );
			const clickEvent = wrapper.emitted( 'click' );
			expect( clickEvent ).toBeTruthy();
			const originalEvent: MouseEvent = clickEvent[ 0 ][ 0 ];
			expect( originalEvent ).toBeInstanceOf( MouseEvent );
			expect( originalEvent.defaultPrevented ).toBeFalsy();
		} );

		it( 'opens the link on enter', () => {
			const wrapper = shallowMountWithProps( {
				href: 'https://example.com',
				preventDefault: false,
			} );
			wrapper.find( 'a' ).trigger( 'keydown.enter' );
			wrapper.find( 'a' ).trigger( 'click.enter' );
			const clickEvents = wrapper.emitted( 'click' );
			expect( clickEvents ).toBeTruthy();
			expect( clickEvents ).toHaveLength( 1 );
			const originalEvent: UIEvent = clickEvents[ 0 ][ 0 ];
			expect( originalEvent ).toBeInstanceOf( UIEvent );
			expect( originalEvent.defaultPrevented ).toBeFalsy();
		} );

		it( 'does nothing on space', () => {
			const wrapper = shallowMountWithProps( {
				href: 'https://example.com',
				preventDefault: false,
			} );
			wrapper.find( 'a' ).trigger( 'keydown.space' );
			expect( wrapper.emitted( 'click' ) ).toBeFalsy();
			expect( wrapper.find( pressedClass ).exists() ).toBeFalsy();
		} );
	} );

	describe( 'if it is disabled', () => {
		it( 'doesn\'t emit an event on click', () => {
			/**
			 * FIXME: this test may be falsely green because of a bug in @vue/test-utils
			 *        see https://github.com/vuejs/vue-test-utils/issues/1321
			 *        It may also be somewhat falsely red because  @vue/test-utils doesn't
			 *        evalute the CSS "pointer-events: none;" rule.
			 */
			const wrapper = shallowMountWithProps( {
				disabled: true,
			} );
			wrapper.find( 'a' ).trigger( 'click' );
			const clickEvent = wrapper.emitted( 'click' );
			expect( clickEvent ).toBeFalsy();
		} );

		it( 'cannot be reached via keyboard without a href', () => {
			const wrapper = shallowMountWithProps( {
				disabled: true,
			} );
			expect( wrapper.find( 'a' ).attributes( 'tabindex' ) ).toBe( '-1' );
		} );

		it( 'cannot be reached via keyboard with a href', () => {
			const wrapper = shallowMountWithProps( {
				disabled: true,
				href: 'https://example.com',
			} );
			expect( wrapper.find( 'a' ).attributes( 'tabindex' ) ).toBe( '-1' );
		} );
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

	it( 'does not have a default href', () => {
		const wrapper = shallowMountWithProps();
		expect( wrapper.find( 'a' ).attributes( 'href' ) ).toBeUndefined();
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

	describe( 'iconOnly modifier', () => {
		it( 'is not added by default', () => {
			const wrapper = shallowMountWithProps( { type: 'primaryProgressive' } );
			expect( wrapper.classes() ).not.toContain( 'wb-ui-event-emitting-button--iconOnly' );
		} );

		it( 'is added if in part of frameless types', () => {
			const wrapper = shallowMountWithProps( { type: 'cancel' } );
			expect( wrapper.classes() ).toContain( 'wb-ui-event-emitting-button--iconOnly' );
		} );
	} );

	describe( 'framless modifier', () => {
		it( 'is not added by default', () => {
			const wrapper = shallowMountWithProps( { type: 'primaryProgressive' } );
			expect( wrapper.classes() ).not.toContain( 'wb-ui-event-emitting-button--frameless' );
		} );

		it( 'is added if in part of frameless types', () => {
			const wrapper = shallowMountWithProps( { type: 'cancel' } );
			expect( wrapper.classes() ).toContain( 'wb-ui-event-emitting-button--frameless' );
		} );
	} );

	it( 'throws for unknown type', () => {
		expect( () => shallowMountWithProps( { type: 'potato' } ) ).toThrow();
	} );

	it( 'supports the cancel type', () => {
		const wrapper = shallowMountWithProps( { type: 'cancel', squary: true } );
		expect( wrapper.classes() ).toContain( 'wb-ui-event-emitting-button--cancel' );
	} );

	it( 'shows a message in its title attribute and text content', () => {
		const message = 'click me';
		const wrapper = shallowMountWithProps( { message } );
		expect( wrapper.find( 'a' ).text() ).toBe( message );
		expect( wrapper.find( 'a' ).attributes( 'title' ) ).toBe( message );
	} );

} );
