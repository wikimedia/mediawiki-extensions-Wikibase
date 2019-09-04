import createEntityModule from '@/store/entity';
import EntityState from '@/store/entity/EntityState';
import ReadingEntityRepository from '@/definitions/data-access/ReadingEntityRepository';
import WritingEntityRepository from '@/definitions/data-access/WritingEntityRepository';

describe( 'store/entity/index', () => {
	it( 'creates the entity module', () => {
		const module = createEntityModule(
			{} as ReadingEntityRepository,
			{} as WritingEntityRepository,
		);
		expect( module ).toBeDefined();
		expect( module.state ).toBeDefined();
		expect( ( module.state as EntityState ).id ).toBe( '' );
		expect( ( module.state as EntityState ).baseRevision ).toBe( 0 );
	} );
} );
