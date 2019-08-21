import { getters } from '@/store/entity/getters';
import newApplicationState from '../newApplicationState';
import newEntityState from './newEntityState';
import {
	ENTITY_ID,
	ENTITY_REVISION,
} from '@/store/entity/getterTypes';

describe( 'entity/Getters', () => {
	it( 'has an id', () => {
		expect( getters[ ENTITY_ID ](
			newEntityState( { id: 'Q123' } ), null, newApplicationState(), null,
		) ).toBe( 'Q123' );
	} );
	it( 'has a baseRevision id', () => {
		expect( getters[ ENTITY_REVISION ](
			newEntityState( { baseRevision: 23 } ), null, newApplicationState(), null,
		) ).toBe( 23 );
	} );
} );
