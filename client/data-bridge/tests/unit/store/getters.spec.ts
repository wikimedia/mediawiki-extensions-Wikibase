import { getters } from '@/store/getters';
import newApplicationState from './newApplicationState';

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
} );
