import { storiesOf } from '@storybook/vue';
import TaintedIcon from '@/presentation/components/TaintedIcon.vue';
import Track from '@/vue-plugins/Track';
import Vue from 'vue';

// eslint-disable-next-line no-console
Vue.use( Track, { trackingFunction: console.log } );

storiesOf( 'TaintedIcon', module )
	.add( 'Just the icon', () => ( {
		components: { TaintedIcon },
		template:
		'<p><TaintedIcon></TaintedIcon></p>',
	} ) );
