import {
	DataValue,
	DataValueType,
} from '@wmde/wikibase-datamodel-types';
import { StatementState } from '@/store/statements/StatementState';
import clone from '@/store/clone';
import UpdateMutationStrategy from '@/change-op/statement-mutation/strategies/UpdateMutationStrategy';
import { MainSnakPath } from '@/store/statements/MainSnakPath';
import StatementMutationError from '@/change-op/statement-mutation/StatementMutationError';

describe( 'UpdateMutationStrategy', () => {
	const mockState: StatementState = {
		Q42: {
			P23: [ {
				id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
				mainsnak: {
					snaktype: 'value',
					property: 'P23',
					datatype: 'string',
					datavalue: {
						value: 'outdated value',
						type: 'string',
					},
				},
				type: 'statement',
				rank: 'preferred',
			} ],
		},
	};

	it( 'throws an error if the snak is missing', () => {
		const mainSnakPath = new MainSnakPath(
			'Q42',
			'P23',
			1,
		);

		const strategy = new UpdateMutationStrategy();

		expect( () => {
			strategy.apply( { type: 'string', value: 'new value' }, mainSnakPath, clone( mockState ) );
		} ).toThrowError( StatementMutationError.NO_SNAK_FOUND );
	} );

	it( 'throws an error if the statement group is missing', () => {
		const mainSnakPath = new MainSnakPath(
			'Q42',
			'P24',
			0,
		);

		const strategy = new UpdateMutationStrategy();

		expect( () => {
			strategy.apply( { type: 'string', value: 'new value' }, mainSnakPath, clone( mockState ) );
		} ).toThrowError( StatementMutationError.NO_STATEMENT_GROUP_FOUND );
	} );

	it( 'throws an error if the datavaluetypes are inconsistent', () => {
		const targetValue: DataValue = {
			type: 'url' as DataValueType,
			value: 'use',
		};

		const mainSnakPath = new MainSnakPath(
			'Q42',
			'P23',
			0,
		);

		const strategy = new UpdateMutationStrategy();

		expect( () => {
			strategy.apply( targetValue, mainSnakPath, clone( mockState ) );
		} ).toThrowError( StatementMutationError.INCONSISTENT_PAYLOAD_TYPE );
	} );

	it( 'adjusts rank of old statement to normal and appends preferred one', () => {
		const targetValue: DataValue = {
			type: 'string',
			value: 'new value',
		};
		const mainSnakPath = new MainSnakPath(
			'Q42',
			'P23',
			0,
		);

		const strategy = new UpdateMutationStrategy();

		const targetState = strategy.apply( targetValue, mainSnakPath, clone( mockState ) );

		const expectedState: StatementState = {
			Q42: {
				P23: [ {
					id: 'Q60$6f832804-4c3f-6185-38bd-ca00b8517765',
					mainsnak: {
						snaktype: 'value',
						property: 'P23',
						datatype: 'string',
						datavalue: {
							value: 'outdated value',
							type: 'string',
						},
					},
					type: 'statement',
					rank: 'normal',
				}, {
					mainsnak: {
						snaktype: 'value',
						property: 'P23',
						datatype: 'string',
						datavalue: targetValue,
					},
					type: 'statement',
					rank: 'preferred',
				} ],
			},
		};

		expect( targetState ).toStrictEqual( expectedState );
	} );
} );
