import { storiesOf } from '@storybook/vue';
import ThankYou from '@/presentation/components/ThankYou';

storiesOf( 'ThankYou', module )
	.addParameters( { component: ThankYou } )
	.add( 'default', () => ( {
		components: { ThankYou },
		template:
			'<ThankYou repoLink="https://example.com" />',
	} ) )
	.add( 'counting CTA clicks', () => ( {
		components: { ThankYou },
		data: () => ( {
			clickCount: 0,
		} ),
		template:
			`<div>
				<ThankYou
					repoLink="https://example.com"
					@opened-reference-edit-on-repo="clickCount++"
				/>
				<p>You clicked the CTA {{ clickCount }} time(s) so far.</p>
			</div>`,
	} ) );
