import SpecialPageReadingEntityRepository from '@/data-access/SpecialPageReadingEntityRepository';
import MwLanguageInfoRepository from '@/data-access/MwLanguageInfoRepository';
import Entities from '@/mock-data/data/Q42.data.json';
import EditFlow from '@/definitions/EditFlow';
import getOrEnforceUrlParameter from '@/mock-data/getOrEnforceUrlParameter';
import ServiceRepositories from '@/services/ServiceRepositories';
import { launch } from '@/main';
import EntityRevision from '@/datamodel/EntityRevision';

const services = new ServiceRepositories();

services.setReadingEntityRepository(
	new SpecialPageReadingEntityRepository(
		{
			get() {
				return Entities;
			},
		} as any, // eslint-disable-line @typescript-eslint/no-explicit-any
		'',
	),
);

services.setWritingEntityRepository(
	{
		saveEntity( _entity: EntityRevision ): Promise<EntityRevision> {
			return Promise.reject();
		},
	},
);

services.setLanguageInfoRepository(
	new MwLanguageInfoRepository(
		{
			bcp47() {
				return 'de';
			},
		},
		{
			getDir() {
				return 'ltr';
			},
		},
	),
);

// TODO: Wire this correctly
services.setEntityLabelRepository( {
	getLabel( _x ) {
		return Promise.resolve( { language: 'de', value: 'Kartoffel' } );
	},
} );

const information = {
	entityId: 'Q42',
	propertyId: getOrEnforceUrlParameter( 'propertyId', 'P349' ) as string,
	editFlow: EditFlow.OVERWRITE,
};

const config = {
	containerSelector: '#data-bridge-container',
};

launch( config, information, services );
