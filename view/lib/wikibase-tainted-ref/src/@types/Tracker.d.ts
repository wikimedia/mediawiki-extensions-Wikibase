/* eslint-disable @typescript-eslint/no-unused-vars */
import Vue from 'vue';

declare module 'vue/types/vue' {
	interface Vue {
		$track( topic: string, data: object|number|string ): void;
	}
}
