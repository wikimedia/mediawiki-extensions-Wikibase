import ThankYou from '@/presentation/components/ThankYou';

export default {
	title: 'ThankYou',
	component: ThankYou,
};

export function normal() {
	return {
		components: { ThankYou },
		template:
			'<ThankYou repoLink="https://example.com" />',
	};
}

export function countingCTAClicks() {
	return {
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
	};
}
