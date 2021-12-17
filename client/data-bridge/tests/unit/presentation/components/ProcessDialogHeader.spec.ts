import ProcessDialogHeader from '@/presentation/components/ProcessDialogHeader.vue';
import { shallowMount } from '@vue/test-utils';

describe( 'ProcessDialogHeader', () => {
	it( 'renders correctly without slots filled', () => {
		const wrapper = shallowMount( ProcessDialogHeader );
		expect( wrapper.element ).toMatchSnapshot();
	} );

	it( 'renders correctly with all props and slots filled', () => {
		const wrapper = shallowMount( ProcessDialogHeader, {
			slots: {
				title: '<span>title</span>',
				primaryAction: '<button>primary action</button>',
				safeAction: '<button>safe action</button>',
			},
		} );
		expect( wrapper.element ).toMatchSnapshot();
	} );

	it( 'gets content through the primaryAction slot', () => {
		const message = 'primary action';
		const wrapper = shallowMount( ProcessDialogHeader, {
			slots: { primaryAction: `<a class="mockPrimaryActionButton">${message}</a>` },
		} );
		expect( wrapper.find( 'a' ).text() ).toBe( message );
	} );

	it( 'gets content through the safeAction slot', () => {
		const message = 'safe action';
		const wrapper = shallowMount( ProcessDialogHeader, {
			slots: { safeAction: `<a class="mockSafeActionButton">${message}</a>` },
		} );
		expect( wrapper.find( 'a' ).text() ).toBe( message );
	} );

	it( 'gets title through the title slot', () => {
		const message = 'some message';
		const wrapper = shallowMount( ProcessDialogHeader, {
			slots: { title: `<span class="mockTitle">${message}</span>` },
		} );
		expect( wrapper.find( 'h1 span' ).text() ).toBe( message );
	} );

} );
