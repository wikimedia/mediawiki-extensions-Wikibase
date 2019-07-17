import { getters } from '@/store/getters';
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
} );
