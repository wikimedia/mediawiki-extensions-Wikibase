import { storiesOf } from '@storybook/vue';

import Initializing from '@/presentation/components/Initializing';

storiesOf( 'Initializing', module )
	.add( 'default', () => ( {
		components: { Initializing },
		template: `<div>
			<label>
				<input type="checkbox" v-model="initializing" />
				Show as still initializing (toggle me)
			</label>
			<Initializing :is-initializing="initializing">Content which may be slow</Initializing>
		</div>`,
		data: () => ( { initializing: true } ),
	} ), { info: true } );
