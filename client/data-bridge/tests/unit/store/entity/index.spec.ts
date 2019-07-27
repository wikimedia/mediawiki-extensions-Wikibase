import createEntityModule from '@/store/entity';
import EntityState from '@/store/entity/EntityState';
import EntityRepository from '@/definitions/data-access/EntityRepository';

describe( 'store/entity/index', () => {
	it( 'creates the entity module', () => {
		const module = createEntityModule( {} as EntityRepository );
		expect( module ).toBeDefined();
		expect( module.state ).toBeDefined();
		expect( ( module.state as EntityState ).id ).toBe( '' );
		expect( ( module.state as EntityState ).baseRevision ).toBe( 0 );
	} );
} );
