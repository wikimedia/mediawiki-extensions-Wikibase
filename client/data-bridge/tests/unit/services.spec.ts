import ServiceRepositories from '@/services/ServiceRepositories';
import ReadingEntityRepository from '@/definitions/data-access/ReadingEntityRepository';

function newServices(): ServiceRepositories {
	return new ServiceRepositories();
}

function newMockReadingEntityRepository(): ReadingEntityRepository {
	return {} as ReadingEntityRepository;
}

describe( 'ServiceRepositories', () => {
	describe( 'ReadingEntityRepository', () => {
		it( 'throws an error if it is not set', () => {
			expect( () => newServices().getReadingEntityRepository() ).toThrow();
		} );

		it( 'can set and get an ReadingEntityRepository', () => {
			const services = newServices();
			const mockReadingEntityRepository = newMockReadingEntityRepository();
			services.setReadingEntityRepository( mockReadingEntityRepository );
			expect( services.getReadingEntityRepository() ).toBe( mockReadingEntityRepository );
		} );
	} );
} );
