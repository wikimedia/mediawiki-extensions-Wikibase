/* eslint-disable @typescript-eslint/no-unused-vars */
import Vue from 'vue';

declare module '@vue/runtime-core' {
	export interface ComponentCustomProperties {
		$track( topic: string, data: object|number|string ): void;
	}
}
