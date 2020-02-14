import { ErrorTypes } from '@/definitions/ApplicationError';
import EditDecision from '@/definitions/EditDecision';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import newApplicationState from './newApplicationState';
import ApplicationStatus, { ValidApplicationStatus } from '@/definitions/ApplicationStatus';
import clone from '@/store/clone';
import { inject } from 'vuex-smart-module';
import { RootGetters } from '@/store/getters';

describe( 'root/getters', () => {
	const entityId = 'Q42';
	const targetProperty = 'P23';

	describe( 'targetLabel', () => {
		it( 'returns the targetProperty and no linguistic content' +
			', if no targetLabel is set.', () => {
			const applicationState = newApplicationState( { targetProperty } );
			const getters = inject( RootGetters, {
				state: applicationState,
			} );

			expect( getters.targetLabel ).toStrictEqual( { value: targetProperty, language: 'zxx' } );
		} );

		it( 'returns the targetLabel term', () => {
			const targetLabel = { language: 'zh', value: '土豆' };
			const applicationState = newApplicationState( { targetLabel } );

			const getters = inject( RootGetters, {
				state: applicationState,
			} );

			expect( getters.targetLabel ).toBe( targetLabel );
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
				},
				[ NS_STATEMENTS ]: {
					[ entityId ]: {
						[ targetProperty ]: [ actualTargetProperty ],
					},
				},
			} );

			const getters = inject( RootGetters, {
				state: applicationState,
			} );

			expect( getters.isTargetStatementModified ).toBe( false );
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
				},
				[ NS_STATEMENTS ]: {
					[ entityId ]: {
						[ targetProperty ]: [ actualTargetProperty ],
					},
				},
			} );

			const getters = inject( RootGetters, {
				state: applicationState,
			} );

			expect( getters.isTargetStatementModified ).toBe( false );
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
				},
				[ NS_STATEMENTS ]: {
					[ entityId ]: {
						[ targetProperty ]: [ actualTargetProperty ],
					},
				},
			} );

			const getters = inject( RootGetters, {
				state: applicationState,
			} );

			expect( getters.isTargetStatementModified ).toBe( true );
		} );
	} );

	describe( 'canSave', () => {

		it.each( [
			[ false, false, null ],
			[ false, true, null ],
			[ false, false, EditDecision.REPLACE ],
			[ true, true, EditDecision.REPLACE ],
			[ true, true, EditDecision.UPDATE ],
		] )(
			'returns %p with isTargetStatementModified=%p and editDecision=%p',
			( expected: boolean, isTargetStatementModified: boolean, editDecision: EditDecision|null ) => {
				const applicationState = newApplicationState( { editDecision } );

				// @ts-ignore
				const getters = inject( RootGetters, {
					state: applicationState,
					getters: {
						isTargetStatementModified,
					},
				} );

				expect( getters.canSave ).toBe( expected );
			},
		);

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
				},
				[ NS_STATEMENTS ]: {
					[ entityId ]: {
						[ targetProperty ]: [ {
							references: expectedTargetReferences,
						} ],
					},
				},
			} );

			const getters = inject( RootGetters, {
				state: applicationState,
			} );

			expect( getters.targetReferences ).toBe( expectedTargetReferences );
		} );

		it( 'returns an empty array, if there are no references', () => {
			const applicationState = newApplicationState( {
				targetProperty,
				applicationStatus: ApplicationStatus.READY,
				[ NS_ENTITY ]: {
					id: entityId,
				},
				[ NS_STATEMENTS ]: {
					[ entityId ]: {
						[ targetProperty ]: [ {} ],
					},
				},
			} );

			const getters = inject( RootGetters, {
				state: applicationState,
			} );

			expect( getters.targetReferences ).toStrictEqual( [] );
		} );

		it( 'returns an empty array, if the application is not ready', () => {
			const applicationState = newApplicationState( {
				targetProperty,
				applicationStatus: ApplicationStatus.INITIALIZING,
				[ NS_ENTITY ]: {
					id: entityId,
				},
				[ NS_STATEMENTS ]: {
					[ entityId ]: {
						[ targetProperty ]: [ {
							references: expectedTargetReferences,
						} ],
					},
				},
			} );

			const getters = inject( RootGetters, {
				state: applicationState,
			} );

			expect( getters.targetReferences ).toStrictEqual( [] );
		} );
	} );

	describe( 'applicationStatus', () => {
		it.each( [ ValidApplicationStatus.INITIALIZING, ValidApplicationStatus.READY ] )(
			'returns underlying valid application status (%s) if there are no errors',
			( status: ValidApplicationStatus ) => {
				const applicationState = newApplicationState( {
					applicationStatus: status,
				} );

				const getters = inject( RootGetters, {
					state: applicationState,
				} );

				expect( getters.applicationStatus ).toBe( status );
			},
		);

		it.each( [ ValidApplicationStatus.INITIALIZING, ValidApplicationStatus.READY ] )(
			'returns error application status instead of "%s" if there are errors',
			( status: ValidApplicationStatus ) => {
				const applicationState = newApplicationState( {
					applicationStatus: status,
					applicationErrors: [ { type: ErrorTypes.APPLICATION_LOGIC_ERROR, info: {} } ],
				} );

				const getters = inject( RootGetters, {
					state: applicationState,
				} );

				expect( getters.applicationStatus ).toBe( ApplicationStatus.ERROR );
			},
		);
	} );

} );
