jest.mock(
	'../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../resources/wikibase.wbui2025/icons.json',
	() => ( {
		cdxIconAdd: 'add',
		cdxIconArrowPrevious: 'arrowPrevious',
		cdxIconCheck: 'check',
		cdxIconClose: 'close',
		cdxIconTrash: 'trash'
	} ),
	{ virtual: true }
);
jest.mock(
	'../../resources/wikibase.wbui2025/api/api.js',
	() => ( { api: { get: jest.fn() } } )
);
jest.mock(
	'../../resources/wikibase.wbui2025/supportedDatatypes.json',
	() => [ 'string', 'tabular-data', 'geo-shape' ],
	{ virtual: true }
);

const crypto = require( 'crypto' );
// eslint-disable-next-line no-undef
Object.defineProperty( globalThis, 'wikibase', {
	value: {
		utilities: {
			ClaimGuidGenerator: class {
				constructor( entityId ) {
					this.entityId = entityId;
				}

				newGuid() {
					return this.entityId + '$' + crypto.randomUUID();
				}
			}
		}
	}
} );

const { mockLibWbui2025 } = require( './libWbui2025Helpers.js' );
mockLibWbui2025();
const editStatementGroupComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.editStatementGroup.vue' );
const editStatementComponent = require( '../../resources/wikibase.wbui2025/wikibase.wbui2025.editStatement.vue' );
const { CdxButton, CdxIcon } = require( '../../codex.js' );
const { mount } = require( '@vue/test-utils' );
const { storeWithStatementsAndProperties } = require( './piniaHelpers.js' );

describe( 'wikibase.wbui2025.editStatementGroup', () => {
	it( 'defines component', async () => {
		expect( typeof editStatementGroupComponent ).toBe( 'object' );
		expect( editStatementGroupComponent )
			.toHaveProperty( 'name', 'WikibaseWbui2025EditStatementGroup' );
	} );

	const mockConfig = {
		wgEditSubmitButtonLabelPublish: false
	};
	mw.config = {
		get: jest.fn( ( key ) => mockConfig[ key ] )
	};

	describe( 'the mounted component', () => {
		async function mountAndGetParts() {
			const testStatement = {
				id: 'Q1$6e87f6d3-444f-405a-8c17-96ff7df34b62',
				mainsnak: {
					snaktype: 'value',
					datavalue: {
						value: '',
						type: 'string'
					},
					datatype: 'string'
				},
				rank: 'normal'
			};
			const wrapper = await mount( editStatementGroupComponent, {
				props: {
					propertyId: 'P1',
					entityId: 'Q123'
				},
				global: {
					plugins: [
						storeWithStatementsAndProperties( { P1: [ testStatement ] } )
					]
				}
			} );
			await wrapper.setData( { editStatementDataLoaded: true } );
			const statementForm = wrapper.findComponent( editStatementComponent );
			const buttons = wrapper.findAllComponents( CdxButton );
			const addValueButton = buttons[ buttons.length - 3 ];
			const closeButton = buttons[ buttons.length - 2 ];
			const publishButton = buttons[ buttons.length - 1 ];
			const backIcon = wrapper.findComponent( CdxIcon );
			return { wrapper, statementForm, addValueButton, closeButton, publishButton, backIcon };
		}

		it( 'mount its child components correctly', async () => {
			const { wrapper, statementForm, addValueButton, closeButton, publishButton, backIcon } = await mountAndGetParts();

			expect( wrapper.exists() ).toBe( true );
			expect( statementForm.exists() ).toBe( true );
			expect( addValueButton.exists() ).toBe( true );
			expect( closeButton.exists() ).toBe( true );
			expect( publishButton.exists() ).toBe( true );
			expect( backIcon.exists() ).toBe( true );

			expect( publishButton.text() ).toContain( 'wikibase-save' );
		} );

		it( 'uses publish message if configured', async () => {
			mockConfig.wgEditSubmitButtonLabelPublish = true;
			const { publishButton } = await mountAndGetParts();

			expect( publishButton.text() ).toContain( 'wikibase-publish' );
		} );

		it( 'emits a hide event when close button is clicked', async () => {
			const { wrapper, closeButton } = await mountAndGetParts();
			await closeButton.trigger( 'click' );
			expect( wrapper.emitted() ).toHaveProperty( 'hide' );
			expect( wrapper.emitted( 'hide' ).length ).toBe( 1 );
		} );

		it( 'emits a hide event when back icon is clicked', async () => {
			const { wrapper, backIcon } = await mountAndGetParts();
			await backIcon.trigger( 'click' );
			expect( wrapper.emitted() ).toHaveProperty( 'hide' );
			expect( wrapper.emitted( 'hide' ).length ).toBe( 1 );
		} );

		it( 'adds a new value when add value is clicked', async () => {
			const { wrapper, addValueButton } = await mountAndGetParts();
			expect( wrapper.vm.editableStatementGuids.length ).toBe( 1 );
			await addValueButton.trigger( 'click' );
			expect( wrapper.vm.editableStatementGuids.length ).toBe( 2 );
		} );

		it( 'removes a value when remove is triggered', async () => {
			const { wrapper, statementForm } = await mountAndGetParts();
			expect( wrapper.vm.editableStatementGuids.length ).toBe( 1 );
			await statementForm.vm.$emit( 'remove', wrapper.vm.editableStatementGuids[ 0 ] );
			expect( wrapper.vm.editableStatementGuids.length ).toBe( 0 );
		} );
	} );
} );
