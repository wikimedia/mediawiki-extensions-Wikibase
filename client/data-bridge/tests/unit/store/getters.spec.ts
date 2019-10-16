import EditFlow from '@/definitions/EditFlow';
import { ENTITY_ID } from '@/store/entity/getterTypes';
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

describe( 'root/getters', () => {
	it( 'has an targetProperty', () => {
		const targetProperty = 'P23';
		const applicationState = newApplicationState( { targetProperty } );
		expect( getters.targetProperty(
			applicationState, null, applicationState, null,
		) ).toBe( targetProperty );
	} );

	it( 'has an editFlow', () => {
		const editFlow = EditFlow.OVERWRITE;
		const applicationState = newApplicationState( { editFlow } );
		expect( getters.editFlow(
			applicationState, null, applicationState, null,
		) ).toBe( editFlow );
	} );

	it( 'has an application status', () => {
		const applicationStatus = ApplicationStatus.READY;
		const applicationState = newApplicationState( { applicationStatus } );
		expect( getters.applicationStatus(
			applicationState, null, applicationState, null,
		) ).toBe( ApplicationStatus.READY );
	} );

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
			const targetProperty = 'P23';
			const entityId = 'Q42';
			const otherGetters = {
				targetProperty,
				[ getter( NS_ENTITY, ENTITY_ID ) ]: entityId,
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

			expect( getters.targetValue(
				applicationState, otherGetters, applicationState, null,
			) ).toBe( dataValue );
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
			const targetProperty = 'P23';
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

	describe( 'stringMaxLength', () => {
		it( 'returns null if Wikibase repo config is not set', () => {
			const applicationState = newApplicationState();

			expect( getters.stringMaxLength(
				applicationState, null, applicationState, null,
			) ).toBeNull();
		} );

		it( 'returns value from Wikibase repo config if set', () => {
			const wikibaseRepoConfiguration = {
				dataTypeLimits: {
					string: {
						maxLength: 12345,
					},
				},
			};
			const applicationState = newApplicationState( { wikibaseRepoConfiguration } );

			expect( getters.stringMaxLength(
				applicationState, null, applicationState, null,
			) ).toBe( 12345 );
		} );
	} );

	describe( 'isTargetPropertyModified', () => {
		const entityId = 'Q42';
		const otherGetters = {
			[ getter( NS_ENTITY, ENTITY_ID ) ]: entityId,
		};
		const targetProperty = 'P23';

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
					[ NS_STATEMENTS ]: {
						[ entityId ]: {
							[ targetProperty ]: [ actualTargetProperty ],
						},
					},
				},
			} );
			expect( getters.isTargetStatementModified(
				applicationState, otherGetters, applicationState, null,
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
					[ NS_STATEMENTS ]: {
						[ entityId ]: {
							[ targetProperty ]: [ actualTargetProperty ],
						},
					},
				},
			} );

			expect( getters.isTargetStatementModified(
				applicationState, otherGetters, applicationState, null,
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
					[ NS_STATEMENTS ]: {
						[ entityId ]: {
							[ targetProperty ]: [ actualTargetProperty ],
						},
					},
				},
			} );
			expect( getters.isTargetStatementModified(
				applicationState, otherGetters, applicationState, null,
			) ).toBe( true );
		} );
	} );
} );
