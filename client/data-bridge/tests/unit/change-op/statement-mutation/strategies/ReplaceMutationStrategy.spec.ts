import {
	DataValue,
	DataValueType,
	Snak,
} from '@wmde/wikibase-datamodel-types';
import ReplaceMutationStrategy from '@/change-op/statement-mutation/strategies/ReplaceMutationStrategy';
import StatementMutationError from '@/change-op/statement-mutation/StatementMutationError';
import { PathToSnak } from '@/store/statements/PathToSnak';
import { StatementState } from '@/store/statements/StatementState';
import clone from '@/store/clone';

describe( 'ReplaceMutationStrategy', () => {
	it( 'rejects if the snak was not found', () => {
		const targetValue: DataValue = {
			type: 'string',
			value: 'a string',
		};
		const mockSnakPath: PathToSnak = {
			resolveSnakInStatement: jest.fn().mockReturnValue( null ),
		};
		const mockState = {};

		const strategy = new ReplaceMutationStrategy();
		expect( () => {
			strategy.apply( targetValue, mockSnakPath, mockState );
		} ).toThrowError( StatementMutationError.NO_SNAK_FOUND );
		expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( mockState );
	} );

	it( 'rejects if the data value type is not the same in targetvalue and state', () => {
		const targetValue: DataValue = {
			type: 'url' as DataValueType,
			value: 'use',
		};
		const mockSnakPath: PathToSnak = {
			resolveSnakInStatement: jest.fn().mockReturnValue( {
				property: 'P42',
				snaktype: 'value',
				datatype: 'string',
				datavalue: {},
			} ),
		};
		const mockState = {};

		const strategy = new ReplaceMutationStrategy();
		expect( () => {
			strategy.apply( targetValue, mockSnakPath, mockState );
		} ).toThrowError( StatementMutationError.INCONSISTENT_PAYLOAD_TYPE );
		expect( mockSnakPath.resolveSnakInStatement ).toHaveBeenCalledWith( mockState );
	} );

	it( 'sets the snaktype and datavalue', () => {
		const targetValue: DataValue = {
			type: 'string',
			value: 'Töfften',
		};
		const resolveSnakInStatement = ( state: StatementState ): Snak => {
			return state.Q42.P23[ 0 ].mainsnak!;
		};
		const mockSnakPath: PathToSnak = {
			resolveSnakInStatement,
		};
		const mockState: StatementState = {
			Q42: {
				P23: [ {
					id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
					mainsnak: {
						snaktype: 'value',
						property: 'P23',
						datatype: 'string',
						datavalue: {
							value: 'Kartoffel',
							type: 'string',
						},
					},
					type: 'statement',
					rank: 'normal',
				} ],
			},
		};

		const strategy = new ReplaceMutationStrategy();

		const targetState = strategy.apply( targetValue, mockSnakPath, clone( mockState ) );

		const snak = resolveSnakInStatement( targetState );
		expect( snak.snaktype ).toStrictEqual( 'value' );
		expect( snak.datavalue! ).toStrictEqual( targetValue );
	} );
} );
