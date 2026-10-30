jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);

const mockConfig = {
	wbCopyright: {
		version: 'wikibase-1',
		messageHtml: 'copyright message with <a>license link</a>'
	}
};
mw.config = {
	get: jest.fn( ( key ) => mockConfig[ key ] ),
	exists: jest.fn( ( key ) => key in mockConfig )
};

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const wbui2025 = require( 'wikibase.wbui2025.lib' );
wbui2025.copyright.persistAcceptance = jest.fn();

const Wbui2025CopyrightDialog = require( '../../../resources/wikibase.wbui2025/components/copyrightDialog.vue' );
const { CdxDialog, CdxCheckbox } = require( '../../../codex.js' );
const { mount } = require( '@vue/test-utils' );

describe( 'wikibase.wbui2025.copyrightDialog', () => {
	it( 'defines component', () => {
		expect( typeof Wbui2025CopyrightDialog ).toBe( 'object' );
		expect( Wbui2025CopyrightDialog ).toHaveProperty( 'name', 'WikibaseWbui2025CopyrightDialog' );
	} );

	describe( 'the mounted component', () => {
		let wrapper, cdxDialog, cdxCheckbox;

		beforeEach( async () => {
			wrapper = await mount( Wbui2025CopyrightDialog, { props: { open: true } } );
			cdxDialog = wrapper.findComponent( CdxDialog );
			cdxCheckbox = wrapper.findComponent( CdxCheckbox );
		} );

		it( 'exists and mounts child components correctly', () => {
			expect( wrapper.exists() ).toBe( true );
			expect( cdxDialog.exists() ).toBe( true );
			expect( cdxCheckbox.exists() ).toBe( true );
			expect( cdxCheckbox.props( 'modelValue' ) ).toBe( false );
		} );

		it( 'checking the checkbox toggles acceptance for future edits', async () => {
			expect( wrapper.vm.$data.persistAcceptance ).toBe( false );
			await cdxCheckbox.vm.$emit( 'update:modelValue', true );
			expect( wrapper.vm.$data.persistAcceptance ).toBe( true );
		} );

		describe( 'clicking the confirm button', () => {
			it( 'calls persistAcceptance and emits "confirm" when the box is checked', async () => {
				await wrapper.setData( { persistAcceptance: true } );
				await cdxDialog.vm.$emit( 'primary' );
				expect( wbui2025.copyright.persistAcceptance ).toHaveBeenCalled();
				expect( wrapper.emitted( 'confirm' ) ).toHaveLength( 1 );
			} );

			it( 'emits "confirm" without calling persistAcceptance when the box is unchecked', async () => {
				await wrapper.setData( { persistAcceptance: false } );
				await cdxDialog.vm.$emit( 'primary' );
				expect( wbui2025.copyright.persistAcceptance ).not.toHaveBeenCalled();
				expect( wrapper.emitted( 'confirm' ) ).toHaveLength( 1 );
			} );
		} );

		describe( 'clicking the cancel button', () => {
			it( 'emits "hide" and does not call persistAcceptance', async () => {
				await wrapper.setData( { persistAcceptance: true } );
				await cdxDialog.vm.$emit( 'default' );
				expect( wbui2025.copyright.persistAcceptance ).not.toHaveBeenCalled();
				expect( wrapper.emitted( 'hide' ) ).toHaveLength( 1 );
			} );
		} );
	} );
} );
