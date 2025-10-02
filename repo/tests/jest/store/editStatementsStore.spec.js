jest.mock(
	'../../../resources/wikibase.wbui2025/api/editEntity.js',
	() => ( { parseValue: jest.fn() } )
);

const { setActivePinia, createPinia } = require( 'pinia' );
const {
	useEditStatementStore,
	useEditStatementsStore,
	useEditSnakStore,
	generateNextSnakKey
} = require( '../../../resources/wikibase.wbui2025/store/editStatementsStore.js' );
const { useParsedValueStore } = require( '../../../resources/wikibase.wbui2025/store/parsedValueStore.js' );
const { useSavedStatementsStore } = require( '../../../resources/wikibase.wbui2025/store/savedStatementsStore.js' );
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
			useEditSnakStore( useEditStatementStore( id1 )().mainSnakKey )().value = v1;
			useEditSnakStore( useEditStatementStore( id2 )().mainSnakKey )().value = v2;

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
			useEditSnakStore( useEditStatementStore( id1 )().mainSnakKey )().snaktype = 'somevalue';
			useEditSnakStore( useEditStatementStore( id2 )().mainSnakKey )().snaktype = 'novalue';

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

		it( 'qualifiers need to be parsed', async () => {
			const parsedValueStore = useParsedValueStore();
			const editStatementsStore = useEditStatementsStore();
			const id1 = 'Q1$00000000-0000-0000-0000-000000000001';
			const v1 = 'value 1';
			editStatementsStore.initializeFromStatementStore( [ id1 ], 'P1' );
			const editStatementStore = useEditStatementStore( id1 )();
			useEditSnakStore( editStatementStore.mainSnakKey )().snaktype = 'novalue';
			const snakKey = generateNextSnakKey();
			useEditSnakStore( snakKey )().initializeWithSnak( {
				property: 'P1',
				snaktype: 'value',
				hash: '5b70b97920708f7e38b0ae3d0d2a0ddbf96899d7',
				datavalue: {
					type: 'string',
					value: v1
				}
			} );
			editStatementStore.qualifiers.P1 = [ snakKey ];
			editStatementStore.qualifiersOrder.push( 'P1' );

			expect( editStatementsStore.isFullyParsed ).toBe( false );

			mockedParseValue.mockResolvedValueOnce( { type: 'string', value: v1 } );
			await parsedValueStore.getParsedValue( 'P1', v1 );

			expect( editStatementsStore.isFullyParsed ).toBe( true );
		} );
	} );

	describe( 'hasChanges getter', () => {
		it( 'empty store has no changes', () => {
			const editStatementsStore = useEditStatementsStore();

			expect( editStatementsStore.hasChanges ).toBe( false );
		} );

		it( 'adding a statement is a change', () => {
			const editStatementsStore = useEditStatementsStore();
			editStatementsStore.createNewBlankStatement( 'Q1$00000000-0000-0000-0000-000000000001', 'P1' );

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );

		it( 'removing a statement is a change', () => {
			const savedStatementsStore = useSavedStatementsStore();
			const id = 'Q1$00000000-0000-0000-0000-000000000001';
			savedStatementsStore.populateWithClaims( { P1: [ {
				id,
				rank: 'normal',
				mainsnak: {
					property: 'P1',
					snaktype: 'novalue',
					hash: 'c77761897897f63f151c4a1deb8bd3ad23ac51c6'
				}
			} ] } );
			const editStatementsStore = useEditStatementsStore();
			editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			expect( editStatementsStore.hasChanges ).toBe( false );

			editStatementsStore.removeStatement( id );

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );

		it( 'changing the snak type from novalue to somevalue is a change', () => {
			const savedStatementsStore = useSavedStatementsStore();
			const id = 'Q1$00000000-0000-0000-0000-000000000001';
			savedStatementsStore.populateWithClaims( { P1: [ {
				id,
				rank: 'normal',
				mainsnak: {
					property: 'P1',
					snaktype: 'novalue',
					hash: 'c77761897897f63f151c4a1deb8bd3ad23ac51c6'
				}
			} ] } );
			const editStatementsStore = useEditStatementsStore();
			editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			useEditSnakStore( useEditStatementStore( id )().mainSnakKey )().snaktype = 'somevalue';

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );

		it( 'changing the rank from normal to preferred is a change', () => {
			const savedStatementsStore = useSavedStatementsStore();
			const id = 'Q1$00000000-0000-0000-0000-000000000001';
			savedStatementsStore.populateWithClaims( { P1: [ {
				id,
				rank: 'normal',
				mainsnak: {
					property: 'P1',
					snaktype: 'novalue',
					hash: 'c77761897897f63f151c4a1deb8bd3ad23ac51c6'
				}
			} ] } );
			const editStatementsStore = useEditStatementsStore();
			editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			useEditStatementStore( id )().rank = 'preferred';

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );

		it( 'adding whitespace to a string value is no change once it’s parsed', async () => {
			const id = 'Q1$00000000-0000-0000-0000-000000000001';
			const statements = { P1: [ {
				id,
				rank: 'normal',
				mainsnak: {
					property: 'P1',
					snaktype: 'value',
					hash: '5b70b97920708f7e38b0ae3d0d2a0ddbf96899d7',
					datatype: 'string',
					datavalue: {
						type: 'string',
						value: 'abc'
					}
				}
			} ] };
			useSavedStatementsStore().populateWithClaims( statements );
			const parsedValueStore = useParsedValueStore();
			parsedValueStore.populateWithStatements( statements );
			const editStatementsStore = useEditStatementsStore();
			editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			useEditSnakStore( useEditStatementStore( id )().mainSnakKey )().value += ' ';

			expect( editStatementsStore.hasChanges ).toBe( null );
			expect( mockedParseValue ).not.toHaveBeenCalled();

			let resolveParsePromise;
			const parsePromise = new Promise( ( resolve ) => {
				resolveParsePromise = resolve;
			} );
			mockedParseValue.mockReturnValueOnce( parsePromise );

			parsedValueStore.getParsedValue( 'P1', 'abc ' );
			expect( mockedParseValue ).toHaveBeenCalledWith( 'P1', 'abc ' );

			expect( editStatementsStore.hasChanges ).toBe( null );

			resolveParsePromise( {
				type: 'string',
				value: 'abc'
			} );
			await Promise.resolve(); // sleep a microtick to let the promise resolution propagate

			expect( editStatementsStore.hasChanges ).toBe( false );
		} );

		it( 'editing a string value is a change once it’s parsed', async () => {
			const id = 'Q1$00000000-0000-0000-0000-000000000001';
			const statements = { P1: [ {
				id,
				rank: 'normal',
				mainsnak: {
					property: 'P1',
					snaktype: 'value',
					hash: '5b70b97920708f7e38b0ae3d0d2a0ddbf96899d7',
					datatype: 'string',
					datavalue: {
						type: 'string',
						value: 'abc'
					}
				}
			} ] };
			useSavedStatementsStore().populateWithClaims( statements );
			const parsedValueStore = useParsedValueStore();
			parsedValueStore.populateWithStatements( statements );
			const editStatementsStore = useEditStatementsStore();
			editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			useEditSnakStore( useEditStatementStore( id )().mainSnakKey )().value += 'd';

			expect( editStatementsStore.hasChanges ).toBe( null );
			expect( mockedParseValue ).not.toHaveBeenCalled();

			let resolveParsePromise;
			const parsePromise = new Promise( ( resolve ) => {
				resolveParsePromise = resolve;
			} );
			mockedParseValue.mockReturnValueOnce( parsePromise );

			parsedValueStore.getParsedValue( 'P1', 'abcd' );
			expect( mockedParseValue ).toHaveBeenCalledWith( 'P1', 'abcd' );

			expect( editStatementsStore.hasChanges ).toBe( null );

			resolveParsePromise( {
				type: 'string',
				value: 'abcd'
			} );
			await Promise.resolve(); // sleep a microtick to let the promise resolution propagate

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );

		it( 'adding a qualifier is a change', async () => {
			const id = 'Q1$00000000-0000-0000-0000-000000000001';
			const statements = { P1: [ {
				id,
				rank: 'normal',
				mainsnak: {
					property: 'P1',
					snaktype: 'value',
					hash: '5b70b97920708f7e38b0ae3d0d2a0ddbf96899d7',
					datatype: 'string',
					datavalue: {
						type: 'string',
						value: 'abc'
					}
				}
			} ] };
			useSavedStatementsStore().populateWithClaims( statements );
			useParsedValueStore().populateWithStatements( statements );
			const editStatementsStore = useEditStatementsStore();
			editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			expect( editStatementsStore.hasChanges ).toBe( false );

			const editStatementStore = useEditStatementStore( id )();
			const snakKey = generateNextSnakKey();
			useEditSnakStore( snakKey )().initializeWithSnak( {
				property: 'P1',
				snaktype: 'value',
				hash: '5b70b97920708f7e38b0ae3d0d2a0ddbf96899d7',
				datavalue: {
					type: 'string',
					value: 'abc'
				}
			} );
			editStatementStore.qualifiers = { P1: [ snakKey ] };
			editStatementStore.qualifiersOrder = [ 'P1' ];

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );

		it( 'removing a qualifier is a change', async () => {
			const id = 'Q1$00000000-0000-0000-0000-000000000001';
			const statements = { P1: [ {
				id,
				rank: 'normal',
				mainsnak: {
					property: 'P1',
					snaktype: 'novalue',
					hash: 'c77761897897f63f151c4a1deb8bd3ad23ac51c6'
				},
				qualifiers: { P1: [ {
					property: 'P1',
					snaktype: 'value',
					hash: '5b70b97920708f7e38b0ae3d0d2a0ddbf96899d7',
					datavalue: {
						type: 'string',
						value: 'abc'
					}
				} ] },
				'qualifiers-order': [ 'P1' ]
			} ] };
			useSavedStatementsStore().populateWithClaims( statements );
			useParsedValueStore().populateWithStatements( statements );
			const editStatementsStore = useEditStatementsStore();
			editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			expect( editStatementsStore.hasChanges ).toBe( false );

			const editStatementStore = useEditStatementStore( id )();
			editStatementStore.qualifiers = {};
			editStatementStore.qualifiersOrder = [];

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );

		// TODO: add tests for editing the qualifier (snak type or value) once editing qualifiers is supported (T405739?)

		it( 'adding a reference is a change', () => {
			const id = 'Q1$00000000-0000-0000-0000-000000000001';
			const statements = { P1: [ {
				id,
				rank: 'normal',
				mainsnak: {
					property: 'P1',
					snaktype: 'value',
					hash: '5b70b97920708f7e38b0ae3d0d2a0ddbf96899d7',
					datatype: 'string',
					datavalue: {
						type: 'string',
						value: 'abc'
					}
				}
			} ] };
			useSavedStatementsStore().populateWithClaims( statements );
			useParsedValueStore().populateWithStatements( statements );
			const editStatementsStore = useEditStatementsStore();
			editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			expect( editStatementsStore.hasChanges ).toBe( false );

			const editStatementStore = useEditStatementStore( id )();
			editStatementStore.references = [ {
				hash: 'd7389795787c3030b9476b7448f3e1eda380b0d9',
				snaks: { P1: [ {
					property: 'P1',
					snaktype: 'value',
					hash: '5b70b97920708f7e38b0ae3d0d2a0ddbf96899d7',
					datavalue: {
						type: 'string',
						value: 'abc'
					}
				} ] },
				'snaks-order': [ 'P1' ]
			} ];

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );

		it( 'removing a reference is a change', () => {
			const id = 'Q1$00000000-0000-0000-0000-000000000001';
			const statements = { P1: [ {
				id,
				rank: 'normal',
				mainsnak: {
					property: 'P1',
					snaktype: 'novalue',
					hash: 'c77761897897f63f151c4a1deb8bd3ad23ac51c6'
				},
				references: [ {
					hash: 'd7389795787c3030b9476b7448f3e1eda380b0d9',
					snaks: { P1: [ {
						property: 'P1',
						snaktype: 'novalue',
						hash: 'c77761897897f63f151c4a1deb8bd3ad23ac51c6'
					} ] },
					'snaks-order': [ 'P1' ]
				} ]
			} ] };
			useSavedStatementsStore().populateWithClaims( statements );
			useParsedValueStore().populateWithStatements( statements );
			const editStatementsStore = useEditStatementsStore();
			editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			expect( editStatementsStore.hasChanges ).toBe( false );

			const editStatementStore = useEditStatementStore( id )();
			editStatementStore.references = [];

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );

		// TODO: add tests for editing the reference once editing references is supported (T405236?)
	} );
} );
