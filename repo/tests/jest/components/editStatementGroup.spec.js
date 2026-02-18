jest.useFakeTimers();

jest.mock(
	'../../../codex.js',
	() => require( '@wikimedia/codex' ),
	{ virtual: true }
);
jest.mock(
	'../../../resources/wikibase.wbui2025/icons.json',
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
	'../../../resources/wikibase.wbui2025/supportedDatatypes.json',
	() => [ 'string', 'tabular-data', 'geo-shape' ],
	{ virtual: true }
);

jest.mock(
	'../../../resources/wikibase.wbui2025/api/editEntity.js',
	() => Object.assign(
		jest.requireActual( '../../../resources/wikibase.wbui2025/api/editEntity.js' ),
		{ renderSnakValueText: jest.fn() }
	)
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

const { ErrorObject } = require( '../../../resources/wikibase.wbui2025/api/api.js' );
const { mockLibWbui2025 } = require( '../libWbui2025Helpers.js' );
mockLibWbui2025();
const {
	renderSnakValueText: mockRenderSnakValueText
} = require( '../../../resources/wikibase.wbui2025/api/editEntity.js' );

const wbui2025 = require( 'wikibase.wbui2025.lib' );
const editStatementGroupComponent = require( '../../../resources/wikibase.wbui2025/components/editStatementGroup.vue' );
const editStatementComponent = require( '../../../resources/wikibase.wbui2025/components/editStatement.vue' );
const { CdxButton, CdxIcon } = require( '../../../codex.js' );
const { mount } = require( '@vue/test-utils' );
const { storeWithStatementsAndProperties } = require( '../piniaHelpers.js' );
const { useParsedValueStore } = require( '../../../resources/wikibase.wbui2025/store/parsedValueStore.js' );
const { useMessageStore } = require( '../../../resources/wikibase.wbui2025/store/messageStore.js' );

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
		const testStatementId = 'Q1$6e87f6d3-444f-405a-8c17-96ff7df34b62';
		const testStatement = {
			id: testStatementId,
			mainsnak: {
				snaktype: 'value',
				property: 'P1',
				datavalue: {
					value: 'a string value',
					type: 'string'
				},
				datatype: 'string'
			},
			rank: 'normal'
		};

		const testQuantityStatement = {
			id: testStatementId,
			mainsnak: {
				snaktype: 'value',
				property: 'P1',
				datavalue: {
					value: {
						amount: '+123',
						unit: '1'
					},
					type: 'quantity'
				},
				datatype: 'quantity'
			},
			rank: 'normal'
		};

		async function mountAndGetParts( statement = testStatement ) {
			const wrapper = await mount( editStatementGroupComponent, {
				props: {
					propertyId: 'P1',
					entityId: 'Q123'
				},
				global: {
					plugins: [
						storeWithStatementsAndProperties( { P1: [ statement ] } )
					],
					disableTeleport: true
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

		it( 'adds a new value when add value is clicked for a quantity-datatype statement', async () => {
			const { wrapper, addValueButton } = await mountAndGetParts( testQuantityStatement );
			expect( wrapper.vm.editableStatementGuids.length ).toBe( 1 );
			expect( mockRenderSnakValueText ).toHaveBeenCalledWith( {
				type: 'quantity',
				value: {
					amount: '+123',
					unit: '1'
				}
			} );
			await addValueButton.trigger( 'click' );
			expect( wrapper.vm.editableStatementGuids.length ).toBe( 2 );
			expect( mockRenderSnakValueText ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'removes a value when remove is triggered', async () => {
			const { wrapper, statementForm } = await mountAndGetParts();
			expect( wrapper.vm.editableStatementGuids.length ).toBe( 1 );
			await statementForm.vm.$emit( 'remove', wrapper.vm.editableStatementGuids[ 0 ] );
			expect( wrapper.vm.editableStatementGuids.length ).toBe( 0 );
		} );

		const updateStatementValue = async function ( publishButton, wrapper ) {
			const editStatementStore = wbui2025.store.useEditStatementStore( testStatementId )();
			const snakStore = wbui2025.store.useEditSnakStore( editStatementStore.mainSnakKey )();
			expect( snakStore.textvalue ).toBe( 'a string value' );
			const parsedValueStore = useParsedValueStore();
			parsedValueStore.preloadParsedValue( 'P1', { value: 'a new string' } );
			snakStore.textvalue = 'a new string';
			await wrapper.vm.$nextTick();

			expect( wrapper.vm.canSubmit ).toBe( true );
			const messageStore = useMessageStore();

			expect( messageStore.messages.size ).toBe( 0 );
			await publishButton.trigger( 'click' );
			await wrapper.vm.$nextTick();
		};

		it( 'shows a success message if publishing succeeds', async () => {
			const { publishButton, wrapper } = await mountAndGetParts();

			useParsedValueStore().populateWithStatements( { P1: [ testStatement ] } );
			const editStatementsStore = wbui2025.store.useEditStatementsStore();
			editStatementsStore.saveChangedStatements = jest.fn(
				() => new Promise( ( resolve ) => {
					setTimeout( resolve, 500 );
				} )
			);
			await updateStatementValue( publishButton, wrapper );

			const messageStore = useMessageStore();
			await jest.advanceTimersByTime( 300 );
			expect( wrapper.vm.showProgress ).toBe( true );
			await jest.advanceTimersByTime( 200 );
			expect( messageStore.messages.size ).toBe( 1 );
			const message = messageStore.messages.values().next().value;
			expect( message.type ).toBe( 'success' );
			expect( wrapper.vm.showProgress ).toBe( false );
		} );

		it( 'shows an error message if publishing fails', async () => {
			const { publishButton, wrapper } = await mountAndGetParts();

			const apiGeneratedErrorMessage = 'Wikibase API reports error for request';
			useParsedValueStore().populateWithStatements( { P1: [ testStatement ] } );
			const editStatementsStore = wbui2025.store.useEditStatementsStore();
			editStatementsStore.saveChangedStatements = jest.fn(
				() => new Promise( ( _, reject ) => {
					const rejectWithException = () => {
						const errorData = { errors: [ { code: 'modification-failed', text: apiGeneratedErrorMessage } ] };
						reject( new ErrorObject( 'modification-failure', apiGeneratedErrorMessage, errorData ) );
					};
					setTimeout( rejectWithException, 500 );
				} )
			);
			await updateStatementValue( publishButton, wrapper );

			const messageStore = useMessageStore();
			await jest.advanceTimersByTime( 300 );
			expect( wrapper.vm.showProgress ).toBe( true );
			await jest.advanceTimersByTime( 200 );
			await Promise.resolve();
			expect( messageStore.messages.size ).toBe( 1 );
			const message = messageStore.messages.values().next().value;
			expect( message.type ).toBe( 'error' );
			expect( message.text ).toBe( 'wikibase-error-save-generic' + '\n' + apiGeneratedErrorMessage );
			await jest.advanceTimersByTime( 300 );
			expect( wrapper.vm.showProgress ).toBe( false );
		} );
	} );
} );
