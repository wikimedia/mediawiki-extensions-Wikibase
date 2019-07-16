import createEntityModule from '@/store/entity';
import EntityState from '@/store/entity/EntityState';

describe( 'store/entity/index', () => {
	it( 'creates the entity module', () => {
		const module = createEntityModule();
		expect( module ).toBeDefined();
		expect( module.state ).toBeDefined();
		expect( ( module.state as EntityState ).id ).toBe( '' );
		expect( ( module.state as EntityState ).baseRevision ).toBe( 0 );
	} );
} );
