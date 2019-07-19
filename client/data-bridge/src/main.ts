import Vue from 'vue';
import App from '@/presentation/App.vue';
import ApplicationConfig from '@/definitions/ApplicationConfig';
import AppInformation from '@/definitions/AppInformation';

Vue.config.productionTip = false;

export function launch( applicationConfig: ApplicationConfig, information: AppInformation ): void {
	// eslint-disable-next-line no-console
	console.log( information );

	new App().$mount( applicationConfig.containerSelector );
}
