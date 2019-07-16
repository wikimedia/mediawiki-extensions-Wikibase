import { ENTITY_ONLY_MAIN_STRING_VALUE } from '@/store/entity/getterTypes';
import { getters } from '@/store/getters';
import namespacedStoreEvent from '@/store/namespacedStoreEvent';
import { NS_ENTITY } from '@/store/namespaces';
import newApplicationState from './newApplicationState';
import ApplicationStatus from '@/store/ApplicationStatus';

describe( 'root/getters', () => {
	it( 'has an targetProperty', () => {
		const targetProperty = 'P23';
		expect( getters.targetProperty(
			newApplicationState( { targetProperty } ), null, null, null,
		) ).toBe( targetProperty );
	} );

	it( 'has an editFlow', () => {
		const editFlow = 'Heraklid';
		expect( getters.editFlow(
			newApplicationState( { editFlow } ), null, null, null,
		) ).toBe( editFlow );
	} );

	it( 'has an application status', () => {
		const applicationStatus = ApplicationStatus.READY;
		expect( getters.applicationStatus( newApplicationState( { applicationStatus } ), null, null, null ) )
			.toBe( ApplicationStatus.READY );
	} );

	it( 'returns the target value', () => {
		const targetProperty = 'P23',
			getter = jest.fn( () => 'a string value' ),
			otherGetters = {
				targetProperty,
				[ namespacedStoreEvent( NS_ENTITY, ENTITY_ONLY_MAIN_STRING_VALUE ) ]: getter,
			};
		expect( getters.targetValue( newApplicationState( { targetProperty } ), otherGetters, null, null ) )
			.toBe( 'a string value' );
		expect( getter ).toHaveBeenCalledWith( targetProperty );
	} );
} );
