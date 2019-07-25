import MwWindow from '@/@types/mediawiki/MwWindow';
import SpecialPageEntityRepository from '@/data-access/SpecialPageEntityRepository';
import { services } from '@/services';
import Vue from 'vue';
import App from '@/presentation/App.vue';
import ApplicationConfig from '@/definitions/ApplicationConfig';
import AppInformation from '@/definitions/AppInformation';

Vue.config.productionTip = false;

export function launch( applicationConfig: ApplicationConfig, information: AppInformation ): void {

	services.setEntityRepository( new SpecialPageEntityRepository(
		( window as MwWindow ).$,
		applicationConfig.specialEntityDataUrl,
	) );

	new App( { data: information } ).$mount( applicationConfig.containerSelector );
}
