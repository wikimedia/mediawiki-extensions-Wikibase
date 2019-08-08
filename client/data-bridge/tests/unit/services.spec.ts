import ServiceRepositories from '@/services/ServiceRepositories';
import EntityRepository from '@/definitions/data-access/EntityRepository';

function newServices(): ServiceRepositories {
	return new ServiceRepositories();
}

function newMockEntityRepository(): EntityRepository {
	return {} as EntityRepository;
}

describe( 'ServiceRepositories', () => {
	describe( 'EntityRepository', () => {
		it( 'throws an error if it is not set', () => {
			expect( () => newServices().getEntityRepository() ).toThrow();
		} );

		it( 'can set and get an EntityRepository', () => {
			const services = newServices();
			const mockEntityRepository = newMockEntityRepository();
			services.setEntityRepository( mockEntityRepository );
			expect( services.getEntityRepository() ).toBe( mockEntityRepository );
		} );
	} );
} );
