import { StatementMutations } from '@/store/statements/mutations';
import newStatementState from './newStatementState';
import StatementMap from '@/datamodel/StatementMap';
import Snak, { SnakType } from '@/datamodel/Snak';
import { inject } from 'vuex-smart-module';
import DataValueType from '@/datamodel/DataValueType';
import { PathToSnak } from '@/store/statements/PathToSnak';

describe( 'statements/Mutations', () => {

	describe( 'general mutations on a statement', () => {

		describe( 'setStatements', () => {
			it( 'sets a new statement', () => {
				const state = newStatementState();

				const statements: StatementMap = {
					P42: [ {
						type: 'statement',
						id: 'Q242$6f832804-4c3f-6185-38bd-ca00b8517765',
						rank: 'normal',
						mainsnak: {} as Snak,
					} ],
				};

				const mutations = inject( StatementMutations, { state } );

				mutations.setStatements( { entityId: 'Q42', statements } );
				expect( state ).toStrictEqual( { Q42: statements } );
			} );
		} );
	} );

	describe( 'snak mutations', () => {

		describe( 'setDataValue', () => {

			it( 'sets new datavalue', () => {
				const mainsnak: Snak = {
					property: 'P42',
					snaktype: 'novalue',
					datatype: 'string',
				};

				const state = newStatementState( { Q42: {
					P42: [ {
						type: 'statement',
						id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
						rank: 'normal',
						mainsnak,
					} ],
				} } );

				const value = {
					type: 'string' as DataValueType,
					value: 'a new string',
				};

				const mockSnakPath: PathToSnak = {
					resolveSnakInStatement: jest.fn().mockReturnValue( mainsnak ),
				};

				const payload = {
					value,
					path: mockSnakPath,
				};

				const mutations = inject( StatementMutations, { state } );
				mutations.setDataValue( payload );

				expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( state );
				expect( state.Q42.P42[ 0 ].mainsnak.datavalue ).toStrictEqual( value );
			} );

			it( 'overwrites old datavalue', () => {
				const mainsnak: Snak = {
					property: 'P42',
					snaktype: 'value',
					datavalue: {
						type: 'string',
						value: 'old string',
					},
					datatype: 'string',
				};

				const state = newStatementState( { Q42: {
					P42: [ {
						type: 'statement',
						id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
						rank: 'normal',
						mainsnak,
					} ],
				} } );

				const value = {
					type: 'string' as DataValueType,
					value: 'a new string',
				};

				const mockSnakPath: PathToSnak = {
					resolveSnakInStatement: jest.fn().mockReturnValue( mainsnak ),
				};

				const payload = {
					value,
					path: mockSnakPath,
				};

				const mutations = inject( StatementMutations, { state } );
				mutations.setDataValue( payload );

				expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( state );
				expect( state.Q42.P42[ 0 ].mainsnak.datavalue ).toStrictEqual( value );
			} );
		} );

		describe( 'setTestSnakType', () => {
			it( 'sets new snak type', () => {
				const mainsnak: Snak = {
					property: 'P42',
					snaktype: 'novalue',
					datatype: 'string',
				};

				const state = newStatementState( { Q42: {
					P42: [ {
						type: 'statement',
						id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
						rank: 'normal',
						mainsnak,
					} ],
				} } );

				const value: SnakType = 'value';

				const mockSnakPath: PathToSnak = {
					resolveSnakInStatement: jest.fn().mockReturnValue( mainsnak ),
				};
				const payload = {
					value,
					path: mockSnakPath,
				};

				const mutations = inject( StatementMutations, { state } );
				mutations.setSnakType( payload );
				expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( state );
				expect( state.Q42.P42[ 0 ].mainsnak.snaktype ).toStrictEqual( value );
			} );
		} );
	} );
} );
