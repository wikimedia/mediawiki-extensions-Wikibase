import { getters } from '@/store/getters';
import { getter } from '@wmde/vuex-helpers/dist/namespacedStoreMethods';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import newApplicationState from './newApplicationState';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import clone from '@/store/clone';
import { InitializedApplicationState } from '@/store/Application';

describe( 'root/getters', () => {
	const entityId = 'Q42';
	const targetProperty = 'P23';

	describe( 'targetValue', () => {
		it( 'returns null if the application is in error state', () => {
			const applicationState = newApplicationState( {
				applicationStatus: ApplicationStatus.ERROR,
			} );
			expect( getters.targetValue(
				applicationState, null, applicationState, null,
			) ).toBeNull();
		} );

		it( 'returns the target value', () => {
			const dataValue = { type: 'string', value: 'a string' };
			const otherGetters = {
				[ getter(
					NS_ENTITY,
					NS_STATEMENTS,
					mainSnakGetterTypes.dataValue,
				) ]: jest.fn( () => {
					return dataValue;
				} ),
			};

			const applicationState = newApplicationState( {
				targetProperty,
				applicationStatus: ApplicationStatus.READY,
			} );

			( applicationState as InitializedApplicationState )[ NS_ENTITY ] = {
				id: entityId,
				baseRevision: 0,
				[ NS_STATEMENTS ]: {},
			};

			expect(
				getters.targetValue( applicationState, otherGetters, applicationState, null ),
			).toBe( dataValue );
			expect(
				otherGetters[ getter(
					NS_ENTITY,
					NS_STATEMENTS,
					mainSnakGetterTypes.dataValue,
				) ],
			).toHaveBeenCalledWith( {
				entityId,
				propertyId: targetProperty,
				index: 0,
			} );
		} );
	} );

	describe( 'targetLabel', () => {
		it( 'returns the targetProperty and no linguistic content' +
			', if no targetLabel is set.', () => {
			const applicationState = newApplicationState( { targetProperty } );

			expect( getters.targetLabel(
				applicationState, null, applicationState, null,
			) ).toStrictEqual( { value: targetProperty, language: 'zxx' } );
		} );

		it( 'returns the targetLabel term', () => {
			const targetLabel = { language: 'zh', value: '土豆' };
			const applicationState = newApplicationState( { targetLabel } );

			expect( getters.targetLabel(
				applicationState, null, applicationState, null,
			) ).toBe( targetLabel );
		} );
	} );

	describe( 'isTargetPropertyModified', () => {

		it( 'returns false if the application is not ready', () => {
			const actualTargetProperty = {
				type: 'statement',
				id: 'opaque statement ID',
				rank: 'normal',
				mainsnak: {
					snaktype: 'value',
					property: 'P60',
					datatype: 'string',
					datavalue: {
						type: 'string',
						value: 'a string value',
					},
				},
			};

			const originalStatement = JSON.stringify( actualTargetProperty );
			actualTargetProperty.mainsnak.datavalue.value = 'modified teststring';
			const applicationState = newApplicationState( {
				targetProperty,
				applicationStatus: ApplicationStatus.INITIALIZING,
				originalStatement,
				[ NS_ENTITY ]: {
					id: entityId,
					[ NS_STATEMENTS ]: {
						[ entityId ]: {
							[ targetProperty ]: [ actualTargetProperty ],
						},
					},
				},
			} );
			expect( getters.isTargetStatementModified(
				applicationState, null, applicationState, null,
			) ).toBe( false );
		} );

		it( 'returns false if there is no diff', () => {
			const actualTargetProperty = {
				type: 'statement',
				id: 'opaque statement ID',
				rank: 'normal',
				mainsnak: {
					snaktype: 'value',
					property: 'P60',
					datatype: 'string',
					datavalue: {
						type: 'string',
						value: 'a string value',
					},
				},
			};

			const originalStatement = clone( actualTargetProperty );
			const applicationState = newApplicationState( {
				targetProperty,
				applicationStatus: ApplicationStatus.READY,
				originalStatement,
				[ NS_ENTITY ]: {
					id: entityId,
					[ NS_STATEMENTS ]: {
						[ entityId ]: {
							[ targetProperty ]: [ actualTargetProperty ],
						},
					},
				},
			} );

			expect( getters.isTargetStatementModified(
				applicationState, null, applicationState, null,
			) ).toBe( false );
		} );

		it( 'returns true if there is a diff', () => {
			const actualTargetProperty = {
				type: 'statement',
				id: 'opaque statement ID',
				rank: 'normal',
				mainsnak: {
					snaktype: 'value',
					property: 'P60',
					datatype: 'string',
					datavalue: {
						type: 'string',
						value: 'a string value',
					},
				},
			};

			const originalStatement = clone( actualTargetProperty );
			actualTargetProperty.mainsnak.datavalue.value = 'modified teststring';

			const applicationState = newApplicationState( {
				targetProperty,
				applicationStatus: ApplicationStatus.READY,
				originalStatement,
				[ NS_ENTITY ]: {
					id: entityId,
					[ NS_STATEMENTS ]: {
						[ entityId ]: {
							[ targetProperty ]: [ actualTargetProperty ],
						},
					},
				},
			} );
			expect( getters.isTargetStatementModified(
				applicationState, null, applicationState, null,
			) ).toBe( true );
		} );
	} );

	describe( 'targetReferences', () => {
		const expectedTargetReferences = [
			{
				snaks: {
					P268: [
						{
							snaktype: 'value',
							property: 'P268',
							hash: '8721e8944f95e9ce185c270dd1e12b81d13f7e9b',
							datavalue: {
								value: '11888092r',
								type: 'string',
							},
							'datatype': 'external-id',
						},
					],
				},
				'snaks-order': [ 'P268' ],
			},
		];

		it( 'returns the references datablob', () => {
			const applicationState = newApplicationState( {
				targetProperty,
				applicationStatus: ApplicationStatus.READY,
				[ NS_ENTITY ]: {
					id: entityId,
					[ NS_STATEMENTS ]: {
						[ entityId ]: {
							[ targetProperty ]: [ {
								references: expectedTargetReferences,
							} ],
						},
					},
				},
			} );

			expect(
				getters.targetReferences( applicationState, null, applicationState, null ),
			).toBe( expectedTargetReferences );
		} );

		it( 'returns an empty array, if there are no references', () => {
			const applicationState = newApplicationState( {
				targetProperty,
				applicationStatus: ApplicationStatus.READY,
				[ NS_ENTITY ]: {
					id: entityId,
					[ NS_STATEMENTS ]: {
						[ entityId ]: {
							[ targetProperty ]: [ {} ],
						},
					},
				},
			} );

			expect(
				getters.targetReferences( applicationState, null, applicationState, null ),
			).toStrictEqual( [] );
		} );

		it( 'returns an empty array, if the application is not ready', () => {
			const applicationState = newApplicationState( {
				targetProperty,
				applicationStatus: ApplicationStatus.INITIALIZING,
				[ NS_ENTITY ]: {
					id: entityId,
					[ NS_STATEMENTS ]: {
						[ entityId ]: {
							[ targetProperty ]: [ {
								references: expectedTargetReferences,
							} ],
						},
					},
				},
			} );

			expect(
				getters.targetReferences( applicationState, null, applicationState, null ),
			).toStrictEqual( [] );
		} );
	} );
} );
