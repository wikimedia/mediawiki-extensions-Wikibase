import { EventEmitter } from 'events';
import AppConfiguration from '@/definitions/AppConfiguration';
import AppInformation from '@/definitions/AppInformation';
import ServiceRepositories from '@/services/ServiceRepositories';

export default interface AppBridge {
	launch(
		config: AppConfiguration,
		info: AppInformation,
		services: ServiceRepositories
	): EventEmitter;
}
