import ServiceContainer, { Services } from '@/services/ServiceContainer';

describe( 'ServiceContainer', () => {
	const serviceNames: ( keyof Services )[] = [
		'readingEntityRepository',
		'writingEntityRepository',
		'languageInfoRepository',
		'entityLabelRepository',
		'referencesRenderingRepository',
		'propertyDatatypeRepository',
		'messagesRepository',
		'wikibaseRepoConfigRepository',
		'tracker',
		'repoRouter',
		'clientRouter',
	];

	describe.each( serviceNames )( '%s', ( name: keyof Services ) => {
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
