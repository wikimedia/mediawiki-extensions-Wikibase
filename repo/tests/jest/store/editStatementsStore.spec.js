jest.mock(
	'../../../resources/wikibase.wbui2025/api/editEntity.js',
	() => ( {
		parseValue: jest.fn(),
		renderSnakValueText: jest.fn(
			( value ) => value.type === 'quantity' ? value.value.amount : value.value
		)
	} )
);
jest.mock(
	'../../../resources/wikibase.wbui2025/repoSettings.json',
	() => ( {
		tabularDataStorageApiEndpointUrl: 'https://commons.test/w/api.php',
		geoShapeStorageApiEndpointUrl: 'https://commons.test/w/api.php'
	} ),
	{ virtual: true }
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

			await editStatementsStore.initializeFromStatementStore( [ id1 ], 'P1' );
			await editStatementsStore.createNewBlankStatement( id2, 'P1' );

			const v1 = 'value 1';
			const v2 = 'value 2';

			const snak1 = useEditSnakStore( useEditStatementStore( id1 )().mainSnakKey )();
			const snak2 = useEditSnakStore( useEditStatementStore( id2 )().mainSnakKey )();

			snak1.textvalue = v1;
			snak1.value = v1;

			snak2.textvalue = v2;
			snak2.value = v2;

			expect( editStatementsStore.isFullyParsed ).toBe( false );

			mockedParseValue.mockResolvedValueOnce( { type: 'string', value: v1 } );
			await parsedValueStore.getParsedValue( 'P1', v1, { property: 'P1' } );

			expect( editStatementsStore.isFullyParsed ).toBe( false );

			mockedParseValue.mockResolvedValueOnce( { type: 'string', value: v2 } );
			await parsedValueStore.getParsedValue( 'P1', v2, { property: 'P1' } );

			expect( editStatementsStore.isFullyParsed ).toBe( true );
		} );

		it( 'somevalue/novalue are fully parsed', async () => {
			const editStatementsStore = useEditStatementsStore();
			const id1 = 'Q1$00000000-0000-0000-0000-000000000001';
			const id2 = 'Q1$00000000-0000-0000-0000-000000000002';
			await editStatementsStore.initializeFromStatementStore( [ id1, id2 ], 'P1' );
			useEditSnakStore( useEditStatementStore( id1 )().mainSnakKey )().snaktype = 'somevalue';
			useEditSnakStore( useEditStatementStore( id2 )().mainSnakKey )().snaktype = 'novalue';

			expect( editStatementsStore.isFullyParsed ).toBe( true );
		} );

		it( 'removed statement does not need to be parsed', async () => {
			const editStatementsStore = useEditStatementsStore();
			const id = 'Q1$00000000-0000-0000-0000-000000000001';
			await editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );
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
			await editStatementsStore.initializeFromStatementStore( [ id1 ], 'P1' );
			const editStatementStore = useEditStatementStore( id1 )();
			useEditSnakStore( editStatementStore.mainSnakKey )().snaktype = 'novalue';
			const snakKey = generateNextSnakKey();
			await useEditSnakStore( snakKey )().initializeWithSnak( {
				property: 'P1',
				snaktype: 'value',
				hash: '5b70b97920708f7e38b0ae3d0d2a0ddbf96899d7',
				datavalue: {
					type: 'string',
					value: v1
				},
				datatype: 'string'
			} );
			editStatementStore.qualifiers.P1 = [ snakKey ];
			editStatementStore.qualifiersOrder.push( 'P1' );

			expect( editStatementsStore.isFullyParsed ).toBe( false );

			mockedParseValue.mockResolvedValueOnce( { type: 'string', value: v1 } );
			await parsedValueStore.getParsedValue( 'P1', v1, { property: 'P1' } );

			expect( editStatementsStore.isFullyParsed ).toBe( true );
		} );

		it( 'references need to be parsed', async () => {
			const parsedValueStore = useParsedValueStore();
			const editStatementsStore = useEditStatementsStore();
			const id1 = 'Q1$00000000-0000-0000-0000-000000000001';
			const v1 = 'value 1';
			await editStatementsStore.initializeFromStatementStore( [ id1 ], 'P1' );
			const editStatementStore = useEditStatementStore( id1 )();
			useEditSnakStore( editStatementStore.mainSnakKey )().snaktype = 'novalue';
			const snakKey = generateNextSnakKey();
			await useEditSnakStore( snakKey )().initializeWithSnak( {
				property: 'P1',
				snaktype: 'value',
				hash: '5b70b97920708f7e38b0ae3d0d2a0ddbf96899d7',
				datavalue: {
					type: 'string',
					value: v1
				},
				datatype: 'string'
			} );
			editStatementStore.references = [ {
				snaks: {
					P1: [ snakKey ]
				},
				'snaks-order': [ 'P1' ]
			} ];
			expect( editStatementsStore.isFullyParsed ).toBe( false );

			mockedParseValue.mockResolvedValueOnce( { type: 'string', value: v1 } );
			await parsedValueStore.getParsedValue( 'P1', v1, { property: 'P1' } );

			expect( editStatementsStore.isFullyParsed ).toBe( true );
		} );

		it( 'newly added references need to be parsed', async () => {
			const parsedValueStore = useParsedValueStore();
			const editStatementsStore = useEditStatementsStore();
			const id1 = 'Q1$00000000-0000-0000-0000-000000000001';
			const v1 = 'value 1';
			await editStatementsStore.initializeFromStatementStore( [ id1 ], 'P1' );
			const editStatementStore = useEditStatementStore( id1 )();
			useEditSnakStore( editStatementStore.mainSnakKey )().snaktype = 'novalue';
			const snakKey = generateNextSnakKey();
			await useEditSnakStore( snakKey )().initializeWithSnak( {
				property: 'P1',
				snaktype: 'value',
				hash: '5b70b97920708f7e38b0ae3d0d2a0ddbf96899d7',
				datavalue: {
					type: 'string',
					value: v1
				},
				datatype: 'string'
			} );
			editStatementStore.references = [ {
				snaks: {},
				'snaks-order': [],
				newSnaks: [ snakKey ]
			} ];
			expect( editStatementsStore.isFullyParsed ).toBe( false );

			mockedParseValue.mockResolvedValueOnce( { type: 'string', value: v1 } );
			await parsedValueStore.getParsedValue( 'P1', v1, { property: 'P1' } );

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

		it( 'removing a statement is a change', async () => {
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
			await editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			expect( editStatementsStore.hasChanges ).toBe( false );

			editStatementsStore.removeStatement( id );

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );

		it( 'changing the snak type from novalue to somevalue is a change', async () => {
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
			await editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			useEditSnakStore( useEditStatementStore( id )().mainSnakKey )().snaktype = 'somevalue';

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );

		it( 'changing the rank from normal to preferred is a change', async () => {
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
			await editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

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
			await editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			const snak = useEditSnakStore( useEditStatementStore( id )().mainSnakKey )();
			snak.textvalue += ' ';
			snak.value = snak.textvalue;

			expect( editStatementsStore.hasChanges ).toBe( null );
			expect( mockedParseValue ).not.toHaveBeenCalled();

			let resolveParsePromise;
			const parsePromise = new Promise( ( resolve ) => {
				resolveParsePromise = resolve;
			} );
			mockedParseValue.mockReturnValueOnce( parsePromise );
			parsedValueStore.getParsedValue( 'P1', 'abc ', { property: 'P1' } );
			expect( mockedParseValue ).toHaveBeenCalledWith( 'abc ', { property: 'P1' } );

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
			await editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			const snak = useEditSnakStore( useEditStatementStore( id )().mainSnakKey )();
			snak.textvalue += 'd';
			snak.value = snak.textvalue;

			expect( editStatementsStore.hasChanges ).toBe( null );
			expect( mockedParseValue ).not.toHaveBeenCalled();

			let resolveParsePromise;
			const parsePromise = new Promise( ( resolve ) => {
				resolveParsePromise = resolve;
			} );
			mockedParseValue.mockReturnValueOnce( parsePromise );

			parsedValueStore.getParsedValue( 'P1', 'abcd', { property: 'P1' } );
			expect( mockedParseValue ).toHaveBeenCalledWith( 'abcd', { property: 'P1' } );

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
			await editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			expect( editStatementsStore.hasChanges ).toBe( false );

			const editStatementStore = useEditStatementStore( id )();
			const snakKey = generateNextSnakKey();
			await useEditSnakStore( snakKey )().initializeWithSnak( {
				property: 'P1',
				snaktype: 'value',
				hash: '5b70b97920708f7e38b0ae3d0d2a0ddbf96899d7',
				datatype: 'string',
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
					datatype: 'string',
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
			await editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			expect( editStatementsStore.hasChanges ).toBe( false );

			const editStatementStore = useEditStatementStore( id )();
			editStatementStore.qualifiers = {};
			editStatementStore.qualifiersOrder = [];

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );

		// TODO: add tests for editing the qualifier (snak type or value) once editing qualifiers is supported (T405739?)

		it( 'adding a reference is a change', async () => {
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
			await editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			expect( editStatementsStore.hasChanges ).toBe( false );

			const editStatementStore = useEditStatementStore( id )();
			editStatementStore.references = [ {
				hash: 'd7389795787c3030b9476b7448f3e1eda380b0d9',
				snaks: { P1: [ {
					property: 'P1',
					snaktype: 'value',
					hash: '5b70b97920708f7e38b0ae3d0d2a0ddbf96899d7',
					datatype: 'string',
					datavalue: {
						type: 'string',
						value: 'abc'
					}
				} ] },
				'snaks-order': [ 'P1' ]
			} ];

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );

		it( 'removing a reference is a change', async () => {
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
			await editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			expect( editStatementsStore.hasChanges ).toBe( false );

			const editStatementStore = useEditStatementStore( id )();
			editStatementStore.references = [];

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );

		it( 'editing a reference snak is a change', async () => {
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
			await editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			expect( editStatementsStore.hasChanges ).toBe( false );

			const editStatementStore = useEditStatementStore( id )();
			const snakKey = editStatementStore.references[ 0 ].snaks.P1[ 0 ];
			const snak = useEditSnakStore( snakKey )();
			snak.snaktype = 'somevalue';

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );

		it( 'removing a snak from a reference is a change', async () => {
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
					snaks: { P1: [
						{
							property: 'P1',
							snaktype: 'novalue',
							hash: 'c77761897897f63f151c4a1deb8bd3ad23ac51c6'
						},
						{
							property: 'P1',
							snaktype: 'somevalue',
							hash: 'c77761897897f63f151c4a1deb8bd3ad23ac51c6'
						}
					] },
					'snaks-order': [ 'P1' ]
				} ]
			} ] };
			useSavedStatementsStore().populateWithClaims( statements );
			useParsedValueStore().populateWithStatements( statements );
			const editStatementsStore = useEditStatementsStore();
			await editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			expect( editStatementsStore.hasChanges ).toBe( false );

			const editStatementStore = useEditStatementStore( id )();
			const snakKeys = editStatementStore.references[ 0 ].snaks.P1;
			editStatementStore.references[ 0 ].snaks.P1 = snakKeys.slice( 1 );

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );

		it( 'adding a new snak to a reference is a change', async () => {
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
					snaks: { P1: [
						{
							property: 'P1',
							snaktype: 'novalue',
							hash: 'c77761897897f63f151c4a1deb8bd3ad23ac51c6'
						}
					] },
					'snaks-order': [ 'P1' ]
				} ]
			} ] };
			useSavedStatementsStore().populateWithClaims( statements );
			useParsedValueStore().populateWithStatements( statements );
			const editStatementsStore = useEditStatementsStore();
			await editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );

			expect( editStatementsStore.hasChanges ).toBe( false );

			const editStatementStore = useEditStatementStore( id )();
			editStatementStore.references[ 0 ].newSnaks = [ 'snak500' ];

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );

		it( 'changing bounds on a quantity is a change once it’s parsed', async () => {
			const id = 'Q1$00000000-0000-0000-0000-000000000001';
			const initialQuantityValue = {
				amount: '+1',
				unit: '1'
			};
			const statements = { P1: [ {
				id,
				rank: 'normal',
				mainsnak: {
					property: 'P1',
					snaktype: 'value',
					hash: '857817e5ad03a3013ebcaa57e39331e3',
					datatype: 'quantity',
					datavalue: {
						type: 'quantity',
						value: initialQuantityValue
					}
				}
			} ] };
			useSavedStatementsStore().populateWithClaims( statements );
			const editStatementsStore = useEditStatementsStore();
			await editStatementsStore.initializeFromStatementStore( [ id ], 'P1' );
			const parsedValueStore = useParsedValueStore();

			const snak = useEditSnakStore( useEditStatementStore( id )().mainSnakKey )();
			snak.textvalue += '+-1';
			const updatedValue = Object.assign( {
				lowerBound: '+0',
				upperBound: '+2'
			}, initialQuantityValue );
			snak.value = updatedValue;

			expect( editStatementsStore.hasChanges ).toBe( null );
			expect( mockedParseValue ).not.toHaveBeenCalled();

			let resolveParsePromise;
			const parsePromise = new Promise( ( resolve ) => {
				resolveParsePromise = resolve;
			} );
			mockedParseValue.mockReturnValueOnce( parsePromise );

			parsedValueStore.getParsedValue( 'P1', '+1+-1', { property: 'P1', options: '{"unit":"1"}' } );
			expect( mockedParseValue ).toHaveBeenCalledWith( '+1+-1', { property: 'P1', options: '{"unit":"1"}' } );

			expect( editStatementsStore.hasChanges ).toBe( null );

			resolveParsePromise( {
				type: 'quantity',
				value: updatedValue
			} );
			await Promise.resolve(); // sleep a microtick to let the promise resolution propagate

			expect( editStatementsStore.hasChanges ).toBe( true );
		} );
	} );
} );

describe( 'Edit Snak Store', () => {
	beforeEach( () => {
		setActivePinia( createPinia() );
	} );

	describe( 'isIncomplete getter', () => {
		it( 'empty snak is not incomplete', () => {
			const editSnakStore = useEditSnakStore( generateNextSnakKey() )();

			expect( editSnakStore.isIncomplete ).toBe( false );
		} );

		it( 'selectionvalue == null => incomplete', () => {
			const editSnakStore = useEditSnakStore( generateNextSnakKey() )();
			expect( editSnakStore.isIncomplete ).toBe( false );

			editSnakStore.selectionvalue = null;

			expect( editSnakStore.isIncomplete ).toBe( true );
		} );

		it( 'textvalue == "" => incomplete', () => {
			const editSnakStore = useEditSnakStore( generateNextSnakKey() )();
			expect( editSnakStore.isIncomplete ).toBe( false );

			editSnakStore.textvalue = '';

			expect( editSnakStore.isIncomplete ).toBe( true );
		} );

	} );

	describe( 'resetToLastCompleteValue', () => {
		it( 'resets textvalue', async () => {
			const editSnakStore = useEditSnakStore( generateNextSnakKey() )();
			const snak = {
				property: 'P1',
				snaktype: 'value',
				hash: '5b70b97920708f7e38b0ae3d0d2a0ddbf96899d7',
				datatype: 'string',
				datavalue: {
					type: 'string',
					value: 'abc'
				}
			};
			await editSnakStore.initializeWithSnak( snak );
			editSnakStore.textvalue = '';
			expect( editSnakStore.isIncomplete ).toBe( true );
			editSnakStore.resetToLastCompleteValue();

			expect( editSnakStore.textvalue ).toBe( 'abc' );
			expect( editSnakStore.isIncomplete ).toBe( false );
		} );

		it( 'resets selectionvalue', async () => {
			const editSnakStore = useEditSnakStore( generateNextSnakKey() )();
			const snak = {
				property: 'P1',
				snaktype: 'value',
				hash: '5b70b97920708f7e38b0ae3d0d2a0ddbf96899d7',
				datavalue: {
					value: { id: 'Q12345', 'numeric-id': 12345, 'entity-type': 'item' },
					type: 'wikibase-entity-id'
				},
				datatype: 'wikibase-item'
			};
			await editSnakStore.initializeWithSnak( snak );
			expect( editSnakStore.selectionvalue ).toBe( 'Q12345' );

			editSnakStore.selectionvalue = null;
			expect( editSnakStore.selectionvalue ).toBe( null );
			expect( editSnakStore.isIncomplete ).toBe( true );

			editSnakStore.resetToLastCompleteValue();
			expect( editSnakStore.selectionvalue ).toBe( 'Q12345' );
			expect( editSnakStore.isIncomplete ).toBe( false );
		} );

		it( 'resets to previous selectionvalue', async () => {
			const editSnakStore = useEditSnakStore( generateNextSnakKey() )();
			const snak = {
				property: 'P1',
				snaktype: 'value',
				hash: '5b70b97920708f7e38b0ae3d0d2a0ddbf96899d7',
				datavalue: {
					value: { id: 'Q123456', 'numeric-id': 12345, 'entity-type': 'item' },
					type: 'wikibase-entity-id'
				},
				datatype: 'wikibase-item'
			};
			await editSnakStore.initializeWithSnak( snak );

			editSnakStore.selectionvalue = 'Q1';
			editSnakStore.textvalue = 'Q1 label';
			expect( editSnakStore.selectionvalue ).toBe( 'Q1' );
			expect( editSnakStore.textvalue ).toBe( 'Q1 label' );
			expect( editSnakStore.isIncomplete ).toBe( false );

			editSnakStore.selectionvalue = null;
			editSnakStore.textvalue = '';
			expect( editSnakStore.selectionvalue ).toBe( null );
			expect( editSnakStore.textvalue ).toBe( '' );
			expect( editSnakStore.isIncomplete ).toBe( true );

			editSnakStore.resetToLastCompleteValue();
			expect( editSnakStore.selectionvalue ).toBe( 'Q1' );
			expect( editSnakStore.textvalue ).toBe( 'Q1 label' );
			expect( editSnakStore.isIncomplete ).toBe( false );
		} );

	} );
} );
