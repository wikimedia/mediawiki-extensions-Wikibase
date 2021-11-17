/* eslint-disable @typescript-eslint/no-unused-vars */
import Vue from 'vue';

declare module '@vue/runtime-core' {
	export interface ComponentCustomProperties {
		$message( key: string ): string;
	}
}
