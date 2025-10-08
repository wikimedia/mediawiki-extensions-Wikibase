jest.mock(
	'../../../resources/wikibase.wbui2025/api/editEntity.js',
	() => ( { parseValue: jest.fn() } )
);

const { setActivePinia, createPinia } = require( 'pinia' );
const { useEditStatementStore, useEditStatementsStore } = require( '../../../resources/wikibase.wbui2025/store/editStatementsStore.js' );
const { useParsedValueStore } = require( '../../../resources/wikibase.wbui2025/store/parsedValueStore.js' );
const { parseValue: mockedParseValue } = require( '../../../resources/wikibase.wbui2025/api/editEntity.js' );

describe( 'Edit Statements Store', () => {
	beforeEach( () => {
		setActivePinia( createPinia() );
	} );

	describe( 'isFullyParsed getter', () => {
		it( 'empty store is fully parsed', () => {
			const editStatementsStore = useEditStatementsStore();

			expect( editStatementsStore.isFullyParsed ).toBe( true );
		} );

		it( 'existing and new statement, initially unparsed, then parsed', async () => {
			const editStatementsStore = useEditStatementsStore();
			const parsedValueStore = useParsedValueStore();
			const id1 = 'Q1$00000000-0000-0000-0000-000000000001';
			const id2 = 'Q1$00000000-0000-0000-0000-000000000002';
			editStatementsStore.initializeFromStatementStore( [ id1 ], 'P1' );
			editStatementsStore.createNewBlankStatement( id2, 'P1' );
			const v1 = 'value 1';
			const v2 = 'value 2';
			useEditStatementStore( id1 )().value = v1;
			useEditStatementStore( id2 )().value = v2;

			expect( editStatementsStore.isFullyParsed ).toBe( false );

			mockedParseValue.mockResolvedValueOnce( { type: 'string', value: v1 } );
			await parsedValueStore.getParsedValue( 'P1', v1 );

			expect( editStatementsStore.isFullyParsed ).toBe( false );

			mockedParseValue.mockResolvedValueOnce( { type: 'string', value: v2 } );
			await parsedValueStore.getParsedValue( 'P1', v2 );

			expect( editStatementsStore.isFullyParsed ).toBe( true );
		} );

		it( 'somevalue/novalue are fully parsed', () => {
			const editStatementsStore = useEditStatementsStore();
			const id1 = 'Q1$00000000-0000-0000-0000-000000000001';
			const id2 = 'Q1$00000000-0000-0000-0000-000000000002';
			editStatementsStore.initializeFromStatementStore( [ id1, id2 ], 'P1' );
			useEditStatementStore( id1 )().snaktype = 'somevalue';
			useEditStatementStore( id2 )().snaktype = 'novalue';

			expect( editStatementsStore.isFullyParsed ).toBe( true );
		} );

		it( 'removed statement does not need to be parsed', () => {
			const editStatementsStore = useEditStatementsStore();
			const id = 'Q1$00000000-0000-0000-0000-000000000001';
			editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );
			useEditStatementStore( id )().value = 'unparsed value';

			expect( editStatementsStore.isFullyParsed ).toBe( false );

			editStatementsStore.removeStatement( id );

			expect( editStatementsStore.isFullyParsed ).toBe( true );
		} );
	} );
} );
