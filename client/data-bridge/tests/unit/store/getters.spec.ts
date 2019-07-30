import EditFlow from '@/definitions/EditFlow';
import { ENTITY_ONLY_MAIN_STRING_VALUE } from '@/store/entity/getterTypes';
import { getters } from '@/store/getters';
import namespacedStoreEvent from '@/store/namespacedStoreEvent';
import { NS_ENTITY } from '@/store/namespaces';
import newApplicationState from './newApplicationState';
import ApplicationStatus from '@/definitions/ApplicationStatus';

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

	it( 'returns the target value', () => {
		const targetProperty = 'P23',
			getter = jest.fn( () => 'a string value' ),
			otherGetters = {
				targetProperty,
				[ namespacedStoreEvent( NS_ENTITY, ENTITY_ONLY_MAIN_STRING_VALUE ) ]: getter,
			};
		const applicationState = newApplicationState( { targetProperty } );
		expect( getters.targetValue(
			applicationState, otherGetters, applicationState, null,
		) ).toBe( 'a string value' );
		expect( getter ).toHaveBeenCalledWith( targetProperty );
	} );
} );
