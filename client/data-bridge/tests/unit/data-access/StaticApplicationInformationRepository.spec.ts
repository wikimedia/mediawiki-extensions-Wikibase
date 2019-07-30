import EditFlow from '@/definitions/EditFlow';
import AppInformation from '@/definitions/AppInformation';
import StaticApplicationInformationRepository from '@/data-access/StaticApplicationInformationRepository';

describe( 'StaticApplicationInformationRepository', () => {
	it( 'returns a information bundle', () => {
		const infos: AppInformation = {
			entityId: 'Q123',
			propertyId: 'P123',
			editFlow: EditFlow.OVERWRITE,
		};
		const infoRepo = new StaticApplicationInformationRepository( infos );
		return infoRepo.getInformation().then( ( repoInfo ) => {
			expect( repoInfo ).toStrictEqual( infos );
		} );
	} );
} );
