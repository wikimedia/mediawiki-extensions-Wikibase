import { storiesOf } from '@storybook/vue';

import Initializing from '@/presentation/components/Initializing';

storiesOf( 'Initializing', module )
	.addParameters( { component: Initializing } )
	.add( 'default', () => ( {
		components: { Initializing },
		template: `<div>
			<label>
				<input type="checkbox" v-model="initializing" />
				Show as still initializing (toggle me)
			</label>
			<div style="min-height: 5em"><!-- prevent progress bar covering label --></div>
			<Initializing :is-initializing="initializing">Content which may be slow</Initializing>
		</div>`,
		data: () => ( { initializing: true } ),
	} ) );
