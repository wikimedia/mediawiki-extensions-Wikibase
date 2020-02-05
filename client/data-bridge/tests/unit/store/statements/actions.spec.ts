import { StatementActions } from '@/store/statements/actions';
import StatementMap from '@/datamodel/StatementMap';
import { inject } from 'vuex-smart-module';
import { PathToSnak } from '@/store/statements/PathToSnak';
import newStatementState from './newStatementState';
import DataValueType from '@/datamodel/DataValueType';
import SnakActionErrors from '@/definitions/storeActionErrors/SnakActionErrors';

describe( 'statement actions', () => {
	describe( 'initStatements', () => {
		it( 'commits to setStatements', () => {
			const payload = {
				entityId: 'Q42',
				statements: {
					P23: [ {
						id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
						mainsnak: {
							snaktype: 'value',
							property: 'P23',
							datatype: 'wikibase-item',
							datavalue: {
								value: {
									'entity-type': 'item',
									id: 'Q6342720',
								},
								type: 'wikibase-entityid',
							},
						},
						type: 'statement',
						rank: 'normal',
					} ],
				} as StatementMap,
			};

			const commit = jest.fn();
			const actions = inject( StatementActions, {
				commit,
			} );

			actions.initStatements( payload );

			expect( commit ).toHaveBeenCalledWith(
				'setStatements',
				payload,
			);
		} );
	} );

	describe( 'actions for snaks', () => {

		describe( 'setStringDataValue', () => {
			it( 'rejects if the snak was not found', async () => {
				const mockSnakPath: PathToSnak = {
					resolveSnakInStatement: jest.fn().mockReturnValue( null ),
				};

				const mockState = newStatementState();
				const actions = inject( StatementActions, {
					state: mockState,
				} );
				const payload = {
					path: mockSnakPath,
					value: {
						type: 'string' as DataValueType,
						value: 'a string',
					},
				};

				expect( actions.setStringDataValue( payload ) )
					.rejects.toStrictEqual( new Error( SnakActionErrors.NO_SNAK_FOUND ) );
				expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( mockState );
			} );

			it( 'rejects if the data value type of the input is not string', async () => {
				const mockSnakPath: PathToSnak = {
					resolveSnakInStatement: jest.fn().mockReturnValue( {
						property: 'P42',
						snaktype: 'value',
						datatype: 'string',
						datavalue: {},
					} ),
				};

				const mockState = newStatementState();
				const actions = inject( StatementActions, {
					state: mockState,
				} );

				const payload = {
					path: mockSnakPath,
					value: {
						type: 'url' as DataValueType,
						value: 'url',
					},
				};

				expect( actions.setStringDataValue( payload ) )
					.rejects.toStrictEqual( new Error( SnakActionErrors.WRONG_PAYLOAD_TYPE ) );
				expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( mockState );
			} );

			it( 'rejects if the data value of the input is not string', async () => {
				const mockSnakPath: PathToSnak = {
					resolveSnakInStatement: jest.fn().mockReturnValue( {
						property: 'P42',
						snaktype: 'value',
						datatype: 'string',
						datavalue: {},
					} ),
				};

				const mockState = newStatementState();
				const actions = inject( StatementActions, {
					state: mockState,
				} );

				const payload = {
					path: mockSnakPath,
					value: {
						type: 'string' as DataValueType,
						value: 42 as any,
					},
				};

				expect( actions.setStringDataValue( payload ) )
					.rejects.toStrictEqual( new Error( SnakActionErrors.WRONG_PAYLOAD_VALUE_TYPE ) );
				expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( mockState );
			} );

			it( 'commits to setSnakType and setDataValue', async () => {
				const commit = jest.fn();
				const mockState = newStatementState();
				const actions = inject( StatementActions, {
					state: mockState,
					commit,
				} );
				const mockSnakPath: PathToSnak = {
					resolveSnakInStatement: jest.fn().mockReturnValue( {
						property: 'P42',
						snaktype: 'novalue',
						datatype: 'string',
					} ),
				};

				const payload = {
					path: mockSnakPath,
					value: {
						type: 'string' as DataValueType,
						value: 'Töfften',
					},
				};

				await expect( actions.setStringDataValue( payload ) ).resolves;
				expect( commit ).toHaveBeenCalledTimes( 2 );
				expect( commit.mock.calls[ 0 ][ 0 ] ).toBe( 'setSnakType' );
				expect( commit.mock.calls[ 0 ][ 1 ] ).toStrictEqual( { path: mockSnakPath, value: 'value' } );
				expect( commit.mock.calls[ 1 ][ 0 ] ).toBe( 'setDataValue' );
				expect( commit.mock.calls[ 1 ][ 1 ] ).toBe( payload );
			} );
		} );
	} );
} );
