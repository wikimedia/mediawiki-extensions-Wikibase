import ServiceContainer, { Services } from '@/services/ServiceContainer';

describe( 'ServiceContainer', () => {
	describe.each( [ [
		'readingEntityRepository',
	], [
		'writingEntityRepository',
	], [
		'languageInfoRepository',
	], [
		'entityLabelRepository',
	], [
		'propertyDatatypeRepository',
	], [
		'messagesRepository',
	], [
		'wikibaseRepoConfigRepository',
	], [
		'tracker',
	] ] )( '%s', ( name: keyof Services ) => {
		it( 'throws an error if it is not set', () => {
			expect( () => ( new ServiceContainer() ).get( name ) ).toThrow();
		} );

		it( 'can set and get it', () => {
			const services = new ServiceContainer();
			const mockService = {};
			( services as any ).set( name, mockService );
			expect( ( services as any ).get( name ) ).toBe( mockService );
		} );
	} );
} );
