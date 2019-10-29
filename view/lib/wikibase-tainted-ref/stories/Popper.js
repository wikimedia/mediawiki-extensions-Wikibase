import { storiesOf } from '@storybook/vue';
import Popper from '@/presentation/components/Popper.vue';

storiesOf( 'Popper', module )
	.add( 'Popper component', () => ( {
		components: { Popper },
		template:
			'<p><Popper></Popper></p>',
	} ) );
