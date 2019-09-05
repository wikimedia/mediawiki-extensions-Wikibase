import SpecialPageReadingEntityRepository from '@/data-access/SpecialPageReadingEntityRepository';
import Entities from '@/mock-data/data/Q42.data.json';
import EditFlow from '@/definitions/EditFlow';
import getOrEnforceUrlParameter from '@/mock-data/getOrEnforceUrlParameter';
import ServiceRepositories from '@/services/ServiceRepositories';
import { launch } from '@/main';

const services = new ServiceRepositories();

services.setReadingEntityRepository(
	new SpecialPageReadingEntityRepository(
		{
			get: () => {
				return Entities;
			},
		} as any, // eslint-disable-line @typescript-eslint/no-explicit-any
		'',
	),
);

const information = {
	entityId: 'Q42',
	propertyId: getOrEnforceUrlParameter( 'propertyId', 'P349' ) as string,
	editFlow: EditFlow.OVERWRITE,
};

const config = {
	containerSelector: '#data-bridge-container',
};

launch( config, information, services );
