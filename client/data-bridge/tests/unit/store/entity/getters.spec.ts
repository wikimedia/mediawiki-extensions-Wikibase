import { getters } from '@/store/entity/getters';
import newEntityState from './newEntityState';

describe( 'entity/Getters', () => {
	it( 'has an id', () => {
		expect( getters.id( newEntityState( { id: 'Q123' } ), null, null, null ) )
			.toBe( 'Q123' );
	} );
	it( 'has a baseRevision id', () => {
		expect( getters.revision( newEntityState( { baseRevision: 23 } ), null, null, null ) )
			.toBe( 23 );
	} );
} );
