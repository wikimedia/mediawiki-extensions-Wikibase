import { storiesOf } from '@storybook/vue';

import Initializing from '@/presentation/components/Initializing';

storiesOf( 'Initializing', module )
	.add( 'loading view', () => ( {
		components: { Initializing },
		template: '<Initializing />',
	} ), { info: true } );
