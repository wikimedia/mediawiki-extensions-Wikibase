import ServiceRepositories from '@/services/ServiceRepositories';

function newServices(): ServiceRepositories {
	return new ServiceRepositories();
}

describe( 'ServiceRepositories', () => {
	describe.each( [ [
		'ReadingEntityRepository',
		'getReadingEntityRepository',
		'setReadingEntityRepository',
	], [
		'WritingEntityRepository',
		'getWritingEntityRepository',
		'setWritingEntityRepository',
	], [
		'LanguageInfoRepository',
		'getLanguageInfoRepository',
		'setLanguageInfoRepository',
	], [
		'EntityLabelRepository',
		'getEntityLabelRepository',
		'setEntityLabelRepository',
	], [
		'MessagesRepository',
		'getMessagesRepository',
		'setMessagesRepository',
	] ] )( '%s', ( _name: string, getter: string, setter: string ) => {
		it( 'throws an error if it is not set', () => {
			expect( () => ( newServices() as any )[ getter ]() ).toThrow();
		} );

		it( 'can set and get it', () => {
			const services = newServices();
			const mockedRepository = {};
			( services as any )[ setter ]( mockedRepository );
			expect( ( services as any )[ getter ]() ).toBe( mockedRepository );
		} );
	} );
} );
