import Vue from 'vue';
import Entities from '@/mock-data/data/Q42.data.json';
import EditFlow from '@/definitions/EditFlow';
import SpecialPageEntityRepository from '@/data-access/SpecialPageEntityRepository';
import getOrEnforceUrlParameter from '@/mock-data/getOrEnforceUrlParameter';
import { services } from '@/services';
import App from '@/presentation/App.vue';
import ApplicationConfig from '@/definitions/ApplicationConfig';
import AppInformation from '@/definitions/AppInformation';

Vue.config.productionTip = false;

function launch( applicationConfig: ApplicationConfig, information: AppInformation ): void {
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

	new App( { data: information } ).$mount( applicationConfig.containerSelector );
}

launch(
	{
		specialEntityDataUrl: '', // we can proudly ignore it
		containerSelector: '#data-bridge-container',
	},
	{
		entityId: 'Q42',
		propertyId: getOrEnforceUrlParameter( 'propertyId', 'P349' ) as string,
		editFlow: EditFlow.OVERWRITE,
	},
);
