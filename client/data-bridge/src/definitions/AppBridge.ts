import { EventEmitter } from 'events';
import AppConfiguration from '@/definitions/AppConfiguration';
import AppInformation from '@/definitions/AppInformation';
import MwWindow from '@/@types/mediawiki/MwWindow';
import ServiceContainer from '@/services/ServiceContainer';
import Tracker from '@/tracking/Tracker';
import { Component, App } from 'vue';

export default interface AppBridge {
	launch(
		createApp: ( rootComponent: Component, rootProps?: Record<string, unknown> | null ) => App,
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
