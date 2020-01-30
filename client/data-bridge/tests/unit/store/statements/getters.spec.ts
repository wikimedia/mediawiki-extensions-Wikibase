import StatementMap from '@/datamodel/StatementMap';
import Snak from '@/datamodel/Snak';
import {
	STATEMENTS_CONTAINS_ENTITY,
	STATEMENTS_IS_AMBIGUOUS,
	STATEMENTS_PROPERTY_EXISTS,
} from '@/store/statements/getterTypes';
import { inject } from 'vuex-smart-module';
import { StatementGetters } from '@/store/statements/getters';
import newStatementState from './newStatementState';
import { PathToSnak } from '@/store/statements/PathToSnak';
import { SNAK_DATA_VALUE, SNAK_DATAVALUETYPE, SNAK_SNAKTYPE } from '@/store/statements/snaks/getterTypes';
import DataValue from '@/datamodel/DataValue';

describe( 'statements/Getters', () => {
	it( 'determines if statements are present for are given entity id', () => {
		const statements = { Q42: {
			P23: [ {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak: {} as Snak,
			} ],
		} as StatementMap };

		const entityId = 'Q42';

		const getters = inject( StatementGetters, {
			state: statements,
		} );

		expect( getters[ STATEMENTS_CONTAINS_ENTITY ]( entityId ) ).toBe( true );

		expect( getters[ STATEMENTS_CONTAINS_ENTITY ]( 'Q23' ) ).toBe( false );

	} );

	it( 'determines if a statement on property exists', () => {
		const statements = { Q42: {
			P23: [ {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak: {} as Snak,
			} ],
		} as StatementMap };
		const entityId = 'Q42';

		const getters = inject( StatementGetters, {
			state: statements,
		} );

		expect( getters[ STATEMENTS_PROPERTY_EXISTS ]( entityId, 'P23' ) ).toBe( true );
		expect( getters[ STATEMENTS_PROPERTY_EXISTS ]( entityId, 'P42' ) ).toBe( false );
		expect( getters[ STATEMENTS_PROPERTY_EXISTS ]( `${entityId}0`, 'P23' ) ).toBe( false );
	} );

	it( 'determines if a statement on property is ambiguous', () => {
		const statements = { Q42: {
			P23: [ {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak: {} as Snak,
			}, {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak: {} as Snak,
			} ],
			P42: [ {
				type: 'statement',
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				rank: 'normal',
				mainsnak: {} as Snak,
			} ],
		} as StatementMap };

		const entityId = 'Q42';

		const getters = inject( StatementGetters, {
			state: statements,
		} );

		expect( getters[ STATEMENTS_IS_AMBIGUOUS ]( entityId, 'P23' ) ).toBe( true );
		expect( getters[ STATEMENTS_IS_AMBIGUOUS ]( entityId, 'P42' ) ).toBe( false );
		expect( getters[ STATEMENTS_IS_AMBIGUOUS ]( entityId, 'P21' ) ).toBe( false );
		expect( getters[ STATEMENTS_IS_AMBIGUOUS ]( `${entityId}0`, 'P23' ) ).toBe( false );
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

				expect( getters[ SNAK_SNAKTYPE ]( mockSnakPath ) ).toBe( returnSnak.snaktype );
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

				expect( getters[ SNAK_SNAKTYPE ]( mockSnakPath ) ).toBeNull();
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

					expect( getters[ SNAK_DATAVALUETYPE ]( mockSnakPath ) ).toStrictEqual( datavalue.type );
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

					expect( getters[ SNAK_DATAVALUETYPE ]( mockSnakPath ) ).toBeNull();
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

					expect( getters[ SNAK_DATA_VALUE ]( mockSnakPath ) ).toStrictEqual( datavalue );
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

					expect( getters[ SNAK_DATA_VALUE ]( mockSnakPath ) ).toBeNull();
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

					expect( getters[ SNAK_DATA_VALUE ]( mockSnakPath ) ).toBeNull();
					expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( mockState );
				} );
			} );
		} );
	} );
} );
