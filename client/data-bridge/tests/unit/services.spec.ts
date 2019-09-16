import ServiceRepositories from '@/services/ServiceRepositories';
import ReadingEntityRepository from '@/definitions/data-access/ReadingEntityRepository';
import WritingEntityRepository from '@/definitions/data-access/WritingEntityRepository';
import LanguageInfoRepository from '@/definitions/data-access/LanguageInfoRepository';
import EntityLabelRepository from '@/definitions/data-access/EntityLabelRepository';
import MessagesRepository from '@/definitions/data-access/MessagesRepository';

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

function newMockEntityLabelRepository(): EntityLabelRepository {
	return {} as EntityLabelRepository;
}

function newMockMessagesRepository(): MessagesRepository {
	return {} as MessagesRepository;
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

	describe( 'MessagesRepository', () => {
		it( 'throws an error if it is not set', () => {
			expect( () => newServices().getMessagesRepository() ).toThrow();
		} );

		it( 'can set and get a MessagesRepository', () => {
			const services = newServices();
			const mockMessagesRepository = newMockMessagesRepository();
			services.setMessagesRepository( mockMessagesRepository );
			expect( services.getMessagesRepository() ).toBe( mockMessagesRepository );
		} );
	} );
} );
