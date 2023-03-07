import {
	DataValue,
	Snak,
} from '@wmde/wikibase-datamodel-types';
import { PathToStatement } from '@/store/statements/PathToStatement';
import { inject } from 'vuex-smart-module';
import { StatementGetters } from '@/store/statements/getters';
import newStatementState from './newStatementState';
import { PathToSnak } from '@/store/statements/PathToSnak';
import { PathToStatementGroup } from '@/store/statements/PathToStatementGroup';
import { StatementState } from '../../../../src/store/statements/StatementState';

describe( 'statements/Getters', () => {
	it( 'determines if statements are present for are given entity id', () => {
		const statements: StatementState = { Q42: {
			P23: [ {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak: {} as Snak,
			} ],
		} };

		const entityId = 'Q42';

		const getters: StatementGetters = inject( StatementGetters, {
			state: statements,
		} );

		expect( getters.containsEntity( entityId ) ).toBe( true );

		expect( getters.containsEntity( 'Q23' ) ).toBe( false );

	} );

	describe( 'propertyExists', () => {

		it( 'returns true if the statement group exists', () => {
			const resolveStatementGroup = jest.fn().mockReturnValue( [] );
			const pathToStatementGroup: PathToStatementGroup = {
				resolveStatementGroup,
			};

			const state: StatementState = {};
			const getters = inject( StatementGetters, {
				state,
			} );
			expect( getters.propertyExists( pathToStatementGroup ) ).toBe( true );
			expect( resolveStatementGroup ).toHaveBeenCalledWith( state );
		} );

		it( 'returns false if the statement group does not exist', () => {
			const resolveStatementGroup = jest.fn().mockReturnValue( null );
			const pathToStatementGroup: PathToStatementGroup = {
				resolveStatementGroup,
			};

			const state: StatementState = {};
			const getters = inject( StatementGetters, {
				state,
			} );
			expect( getters.propertyExists( pathToStatementGroup ) ).toBe( false );
			expect( resolveStatementGroup ).toHaveBeenCalledWith( state );
		} );
	} );

	describe( 'isStatementGroupAmbiguous', () => {
		it( 'returns false if the statement group has one statement', () => {
			const resolveStatementGroup = jest.fn().mockReturnValue( [ {} ] );
			const pathToStatementGroup: PathToStatementGroup = {
				resolveStatementGroup,
			};

			const state: StatementState = {};
			const getters = inject( StatementGetters, {
				state,
			} );
			expect( getters.isStatementGroupAmbiguous( pathToStatementGroup ) ).toBe( false );
			expect( resolveStatementGroup ).toHaveBeenCalledWith( state );
		} );

		it( 'returns true if the statement group has two statements', () => {
			const resolveStatementGroup = jest.fn().mockReturnValue( [ {}, {} ] );
			const pathToStatementGroup: PathToStatementGroup = {
				resolveStatementGroup,
			};

			const state: StatementState = {};
			const getters = inject( StatementGetters, {
				state,
			} );
			expect( getters.isStatementGroupAmbiguous( pathToStatementGroup ) ).toBe( true );
			expect( resolveStatementGroup ).toHaveBeenCalledWith( state );
		} );

		it( 'returns false if the statement group does not exist', () => {
			const resolveStatementGroup = jest.fn().mockReturnValue( null );
			const pathToStatementGroup: PathToStatementGroup = {
				resolveStatementGroup,
			};

			const state: StatementState = {};
			const getters = inject( StatementGetters, {
				state,
			} );
			expect( getters.isStatementGroupAmbiguous( pathToStatementGroup ) ).toBe( false );
			expect( resolveStatementGroup ).toHaveBeenCalledWith( state );
		} );
	} );

	describe( 'getters for statements', () => {
		describe( 'rank', () => {
			it( 'has a rank', () => {
				const returnStatement = {
					rank: 'normal',
				};

				const mockState = newStatementState();
				const getters = inject( StatementGetters, {
					state: mockState,
				} );

				const mockStatementPath: PathToStatement = {
					resolveStatement: jest.fn().mockReturnValue( returnStatement ),
				};

				expect( getters.rank( mockStatementPath ) ).toBe( returnStatement.rank );
				expect( mockStatementPath.resolveStatement ).toHaveBeenCalledWith( mockState );
			} );

			it( 'returns null if statement was not found', () => {
				const mockState = newStatementState();
				const getters = inject( StatementGetters, {
					state: mockState,
				} );

				const mockStatementPath: PathToStatement = {
					resolveStatement: jest.fn().mockReturnValue( null ),
				};

				expect( getters.rank( mockStatementPath ) ).toBeNull();
				expect( mockStatementPath.resolveStatement ).toHaveBeenCalledWith( mockState );
			} );
		} );
	} );

	describe( 'getters for snaks', () => {
		describe( 'snaktype', () => {
			it( 'has a snaktype', () => {
				const returnSnak = {
					property: 'P42',
					snaktype: 'somevalue',
					datatype: 'url',
				};

				const mockState = newStatementState();
				const getters = inject( StatementGetters, {
					state: mockState,
				} );

				const mockSnakPath: PathToSnak = {
					resolveSnakInStatement: jest.fn().mockReturnValue( returnSnak ),
				};

				expect( getters.snakType( mockSnakPath ) ).toBe( returnSnak.snaktype );
				expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( mockState );
			} );

			it( 'return null if snak was not found', () => {
				const mockState = newStatementState();
				const getters = inject( StatementGetters, {
					state: mockState,
				} );

				const mockSnakPath: PathToSnak = {
					resolveSnakInStatement: jest.fn().mockReturnValue( null ),
				};

				expect( getters.snakType( mockSnakPath ) ).toBeNull();
				expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( mockState );
			} );
		} );

		describe( 'datatype', () => {
			it( 'has a datatype', () => {
				const returnSnak = {
					property: 'P42',
					snaktype: 'somevalue',
					datatype: 'url',
				};

				const mockState = newStatementState();
				const getters = inject( StatementGetters, {
					state: mockState,
				} );

				const mockSnakPath: PathToSnak = {
					resolveSnakInStatement: jest.fn().mockReturnValue( returnSnak ),
				};

				expect( getters.dataType( mockSnakPath ) ).toBe( returnSnak.datatype );
				expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( mockState );
			} );

			it( 'returns null if snak was not found', () => {
				const mockState = newStatementState();
				const getters = inject( StatementGetters, {
					state: mockState,
				} );

				const mockSnakPath: PathToSnak = {
					resolveSnakInStatement: jest.fn().mockReturnValue( null ),
				};

				expect( getters.dataType( mockSnakPath ) ).toBeNull();
				expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( mockState );
			} );
		} );

		describe( 'datavalues', () => {
			describe( 'datavalue type', () => {
				it( 'gets a datavaluetype', () => {
					const datavalue: DataValue = {
						type: 'string',
						value: 'I am a string',
					};

					const returnSnak = {
						property: 'P42',
						snaktype: 'value',
						datatype: 'string',
						datavalue,
					};

					const mockState = newStatementState();
					const getters = inject( StatementGetters, {
						state: mockState,
					} );

					const mockSnakPath: PathToSnak = {
						resolveSnakInStatement: jest.fn().mockReturnValue( returnSnak ),
					};

					expect( getters.dataValueType( mockSnakPath ) ).toStrictEqual( datavalue.type );
					expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( mockState );
				} );

				it( 'returns null if snak was not found', () => {
					const mockState = newStatementState();
					const getters = inject( StatementGetters, {
						state: mockState,
					} );

					const mockSnakPath: PathToSnak = {
						resolveSnakInStatement: jest.fn().mockReturnValue( null ),
					};

					expect( getters.dataValueType( mockSnakPath ) ).toBeNull();
					expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( mockState );
				} );
			} );

			describe( 'datavalue value', () => {
				it( 'contains a value', () => {
					const datavalue: DataValue = {
						type: 'string',
						value: 'I am a string',
					};

					const returnSnak = {
						property: 'P42',
						snaktype: 'value',
						datatype: 'string',
						datavalue,
					};

					const mockState = newStatementState();
					const getters = inject( StatementGetters, {
						state: mockState,
					} );

					const mockSnakPath: PathToSnak = {
						resolveSnakInStatement: jest.fn().mockReturnValue( returnSnak ),
					};

					expect( getters.dataValue( mockSnakPath ) ).toStrictEqual( datavalue );
					expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( mockState );
				} );

				it( 'returns null if datavalue is missing', () => {
					const returnSnak = {
						property: 'P42',
						snaktype: 'value',
						datatype: 'string',
					};

					const mockState = newStatementState();
					const getters = inject( StatementGetters, {
						state: mockState,
					} );

					const mockSnakPath: PathToSnak = {
						resolveSnakInStatement: jest.fn().mockReturnValue( returnSnak ),
					};

					expect( getters.dataValue( mockSnakPath ) ).toBeNull();
					expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( mockState );
				} );

				it( 'returns null if the snak was not found', () => {
					const mockState = newStatementState();
					const getters = inject( StatementGetters, {
						state: mockState,
					} );

					const mockSnakPath: PathToSnak = {
						resolveSnakInStatement: jest.fn().mockReturnValue( null ),
					};

					expect( getters.dataValue( mockSnakPath ) ).toBeNull();
					expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( mockState );
				} );
			} );
		} );
	} );
} );
