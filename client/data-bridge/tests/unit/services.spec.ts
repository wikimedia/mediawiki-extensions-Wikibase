import { ServiceRepositories } from '@/services';
import EntityRepository from '@/definitions/data-access/EntityRepository';
import ApplicationInformationRepository from '@/definitions/data-access/ApplicationInformationRepository';

function newServices(): ServiceRepositories {
	return new ServiceRepositories();
}

function newMockEntityRepository(): EntityRepository {
	return {} as EntityRepository;
}

function newMockApplicationInformationRepository(): ApplicationInformationRepository {
	return {} as ApplicationInformationRepository;
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

	describe( 'ApplicationInformationRepository', () => {
		it( 'throws an error if it is not set', () => {
			expect( () => newServices().getApplicationInformationRepository() ).toThrow();
		} );

		it( 'can set and get an ApplicationRepository', () => {
			const services = newServices();
			const newRepo = newMockApplicationInformationRepository();
			services.setApplicationInformationRepository( newRepo );
			expect( services.getApplicationInformationRepository() ).toBe( newRepo );
		} );
	} );
} );
