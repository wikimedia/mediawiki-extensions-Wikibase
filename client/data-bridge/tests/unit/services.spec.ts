import ServiceRepositories from '@/services/ServiceRepositories';
import ReadingEntityRepository from '@/definitions/data-access/ReadingEntityRepository';
import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';
import EntityLabelRepository from '@/definitions/data-access/EntityLabelRepository';

function newServices(): ServiceRepositories {
	return new ServiceRepositories();
}

function newMockReadingEntityRepository(): ReadingEntityRepository {
	return {} as ReadingEntityRepository;
}

function newMockLanguageInfoRepository(): LanguageInfoRepository {
	return {} as LanguageInfoRepository;
}

function newMockEntityLabelRepository(): EntityLabelRepository {
	return {} as EntityLabelRepository;
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

	describe( 'LanguageInfoRepository', () => {
		it( 'throws an error if it is not set', () => {
			expect( () => newServices().getLanguageInfoRepository() ).toThrow();
		} );
		it( 'can set and get an LanguageInfoRepository', () => {
			const services = newServices();
			const mockLanguageInfoRepository = newMockLanguageInfoRepository();
			services.setLanguageInfoRepository( mockLanguageInfoRepository );
			expect( services.getLanguageInfoRepository() ).toBe( mockLanguageInfoRepository );
		} );
	} );

	describe( 'EntityLabelRepository', () => {
		it( 'throws an error if it is not set', () => {
			expect( () => newServices().getEntityLabelRepository() ).toThrow();
		} );

		it( 'can set and get an EntityLabelRepository', () => {
			const services = newServices();
			const mockEntityLabelRepository = newMockEntityLabelRepository();
			services.setEntityLabelRepository( mockEntityLabelRepository );
			expect( services.getEntityLabelRepository() ).toBe( mockEntityLabelRepository );
		} );
	} );
} );
