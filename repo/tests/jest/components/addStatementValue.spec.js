jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/icons.json',
	() => ( {
		cdxIconClose: 'close',
		cdxIconCheck: 'check'
	} ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/supportedDatatypes.json',
	() => [ 'string', 'tabular-data', 'geo-shape' ],
	{ virtual: true }
);

const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();

const { mount } = require( '@vue/test-utils' );
const AddStatementValue = require(
	'../../../resources/wikibase.wbui2025/components/addStatementValue.vue'
);
const { storeWithStatementsAndProperties } = require( '../piniaHelpers.js' );

/**
 * Stubs — matching Wikibase patterns
 */
const stubs = {
	WikibaseWbui2025EditStatement: {
		template: '<div class="stub-edit-statement"></div>'
	},
	CdxButton: { template: '<button><slot /></button>' },
	CdxIcon: { template: '<span class="stub-icon"></span>' }
};

function mountComponent( props = {} ) {
	return mount( AddStatementValue, {
		props: Object.assign(
			{
				statementId: 'Q123$AAA'
			},
			props
		),
		global: {
			plugins: [ storeWithStatementsAndProperties( {} ) ],
			stubs: Object.assign( { teleport: true }, stubs )
		}
	} );
}

describe( 'wikibase.wbui2025.addStatementValue', () => {
	it( 'defines the component', () => {
		expect( AddStatementValue ).toHaveProperty(
			'name',
			'WikibaseWbui2025AddStatementValue'
		);
	} );

	it( 'renders modal when visible=true', () => {
		const wrapper = mountComponent();
		expect(
			wrapper.findComponent( { name: 'WikibaseWbui2025ModalOverlay' } ).exists()
		).toBe( true );
	} );

	it( 'renders edit-statement area inside modal content', () => {
		const wrapper = mountComponent();
		const overlay = wrapper.findComponent( { name: 'WikibaseWbui2025ModalOverlay' } );
		expect( overlay.find( '.stub-edit-statement' ).exists() ).toBe( true );
	} );

	it( 'renders exactly two footer buttons (cancel + add)', () => {
		const wrapper = mountComponent();
		const overlay = wrapper.findComponent( { name: 'WikibaseWbui2025ModalOverlay' } );
		const footer = overlay.find( '.wikibase-wbui2025-edit-statement-footer' );
		const btns = footer.findAll( 'button' );
		expect( btns.length ).toBe( 2 );
	} );

	it( 'cancel button emits "cancel"', async () => {
		const wrapper = mountComponent();
		const overlay = wrapper.findComponent( { name: 'WikibaseWbui2025ModalOverlay' } );
		const footer = overlay.find( '.wikibase-wbui2025-edit-statement-footer' );
		const cancelBtn = footer.findAll( 'button' )[ 0 ];
		await cancelBtn.trigger( 'click' );
		expect( wrapper.emitted().cancel ).toBeTruthy();
		expect( wrapper.emitted().cancel.length ).toBe( 1 );
	} );

	it( 'does NOT emit add when form invalid (canSubmit=false)', async () => {
		const wrapper = mountComponent();
		const overlay = wrapper.findComponent( { name: 'WikibaseWbui2025ModalOverlay' } );
		const footer = overlay.find( '.wikibase-wbui2025-edit-statement-footer' );
		const addBtn = footer.findAll( 'button' )[ 1 ];
		expect( wrapper.vm.canSubmit ).toBe( false );
		await addBtn.trigger( 'click' );
		expect( wrapper.emitted().add ).toBeUndefined();
	} );

	it( 'emits add(statementId) when valid (canSubmit=true)', async () => {
		const wrapper = mountComponent( { statementId: 'Q123$NEW' } );
		const store = wrapper.vm.$pinia._s.get( 'editStatements' );
		store.hasChanges = true;
		store.fullyParsed = true;
		await wrapper.vm.$nextTick();
		expect( wrapper.vm.canSubmit ).toBe( true );

		const overlay = wrapper.findComponent( { name: 'WikibaseWbui2025ModalOverlay' } );
		const footer = overlay.find( '.wikibase-wbui2025-edit-statement-footer' );
		const addBtn = footer.findAll( 'button' )[ 1 ];
		await addBtn.trigger( 'click' );

		expect( wrapper.emitted().add ).toBeTruthy();
		expect( wrapper.emitted().add[ 0 ][ 0 ] ).toBe( 'Q123$NEW' );
	} );
} );
