import Vue from 'vue';
import Track from '@/vue-plugins/Track';
import { launch } from '@/main';
import CSRHookHandler from '@/CSRHookHandler';

( () => {
	function trackMock( topic: string, data?: object ): void {
		// eslint-disable-next-line no-console
		console.debug( `tracked: ${topic}`, data );
	}

	Vue.use( Track, { trackingFunction: trackMock } );
} )();

launch( new CSRHookHandler(), 'https://www.wikidata.org/wiki/Special:MyLanguage/Help:Sources' );
