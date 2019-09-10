import ServiceRepositories from '@/services/ServiceRepositories';
import ReadingEntityRepository from '@/definitions/data-access/ReadingEntityRepository';
import WritingEntityRepository from '@/definitions/data-access/WritingEntityRepository';
import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';

function newServices(): ServiceRepositories {
	return new ServiceRepositories();
}

function newMockReadingEntityRepository(): ReadingEntityRepository {
	return {} as ReadingEntityRepository;
}

function newMockWritingEntityRepository(): WritingEntityRepository {
	return {} as WritingEntityRepository;
}

function newMockLanguageInfoRepository(): LanguageInfoRepository {
	return {} as LanguageInfoRepository;
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

	describe( 'WritingEntityRepository', () => {
		it( 'throws an error if it is not set', () => {
			expect( () => newServices().getWritingEntityRepository() ).toThrow();
		} );

		it( 'can set and get an WritingEntityRepository', () => {
			const services = newServices();
			const mockWritingEntityRepository = newMockWritingEntityRepository();

			services.setWritingEntityRepository( mockWritingEntityRepository );
			expect( services.getWritingEntityRepository() ).toBe( mockWritingEntityRepository );
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
} );
