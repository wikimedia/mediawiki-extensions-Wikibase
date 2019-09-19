import { storiesOf } from '@storybook/vue';
import TaintedIcon from '@/presentation/components/TaintedIcon.vue';

storiesOf( 'TaintedIcon', module )
	.add( 'Just the icon', () => ( {
		components: { TaintedIcon },
		template:
		'<p><TaintedIcon></TaintedIcon></p>',
	} ) );
