import { EventEmitter } from 'events';
import AppConfiguration from '@/definitions/AppConfiguration';
import AppInformation from '@/definitions/AppInformation';
import MwWindow from '@/@types/mediawiki/MwWindow';
import ServiceContainer from '@/services/ServiceContainer';
import Tracker from '@/tracking/Tracker';

export default interface AppBridge {
	launch(
		config: AppConfiguration,
		info: AppInformation,
		services: ServiceContainer
	): EventEmitter;

	createServices(
		mwWindow: MwWindow,
		editTags: readonly string[],
		eventTracker: Tracker,
	): ServiceContainer;
}
