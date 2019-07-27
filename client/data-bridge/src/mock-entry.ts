import Vue from 'vue';
import Entities from '@/mock-data/data/Q42.data.json';
import EditFlow from '@/definitions/EditFlow';
import SpecialPageEntityRepository from '@/data-access/SpecialPageEntityRepository';
import StaticApplicationInformationRepository from '@/data-access/StaticApplicationInformationRepository';
import getOrEnforceUrlParameter from '@/mock-data/getOrEnforceUrlParameter';
import { services } from '@/services';
import App from '@/presentation/App.vue';
import { createStore } from '@/store';

Vue.config.productionTip = false;

services.setEntityRepository(
	new SpecialPageEntityRepository(
		{
			get: () => {
				return Entities;
			},
		} as any, // eslint-disable-line @typescript-eslint/no-explicit-any
		'',
	),
);

services.setApplicationInformationRepository(
	new StaticApplicationInformationRepository(
		{
			entityId: 'Q42',
			propertyId: getOrEnforceUrlParameter( 'propertyId', 'P349' ) as string,
			editFlow: EditFlow.OVERWRITE,
		},
	),
);

new App( { store: createStore() } ).$mount( '#data-bridge-container' );
