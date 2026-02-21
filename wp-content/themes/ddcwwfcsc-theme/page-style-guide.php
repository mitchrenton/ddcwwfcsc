<?php
/**
 * Template Name: Style Guide
 *
 * A reference page showing all theme components.
 *
 * @package DDCWWFCSC_Theme
 */

get_header();
?>

<main class="site-main" role="main">
	<div class="container">
		<header class="page-header">
			<h1 class="page-title"><?php esc_html_e( 'Style Guide', 'ddcwwfcsc-theme' ); ?></h1>
			<p class="page-description"><?php esc_html_e( 'A reference for all theme design tokens, typography, and components.', 'ddcwwfcsc-theme' ); ?></p>
		</header>

		<!-- Colours -->
		<section class="style-guide-section">
			<h2 class="section-heading"><?php esc_html_e( 'Colours', 'ddcwwfcsc-theme' ); ?></h2>
			<div class="style-guide-swatches">
				<div class="style-guide-swatch" style="background:var(--color-gold);color:#000;">Gold</div>
				<div class="style-guide-swatch" style="background:var(--color-gold-hover);color:#000;">Gold Hover</div>
				<div class="style-guide-swatch" style="background:var(--color-gold-light);color:#000;">Gold Light</div>
				<div class="style-guide-swatch" style="background:var(--color-black);color:#fff;">Black</div>
				<div class="style-guide-swatch" style="background:var(--color-grey-900);color:#fff;">Grey 900</div>
				<div class="style-guide-swatch" style="background:var(--color-grey-600);color:#fff;">Grey 600</div>
				<div class="style-guide-swatch" style="background:var(--color-grey-300);color:#000;">Grey 300</div>
				<div class="style-guide-swatch" style="background:var(--color-grey-100);color:#000;">Grey 100</div>
			</div>
		</section>

		<!-- Typography -->
		<section class="style-guide-section">
			<h2 class="section-heading"><?php esc_html_e( 'Typography', 'ddcwwfcsc-theme' ); ?></h2>
			<h1>Heading 1 &mdash; Playfair Display</h1>
			<h2>Heading 2 &mdash; Playfair Display</h2>
			<h3>Heading 3 &mdash; Playfair Display</h3>
			<h4>Heading 4 &mdash; Playfair Display</h4>
			<h5>Heading 5 &mdash; Playfair Display</h5>
			<h6>Heading 6 &mdash; Playfair Display</h6>
			<p>Body text is set in Inter at 1rem/1.7. This is a paragraph with enough text to show the line height and max-width constraint of the <code>.entry-content</code> container at 72ch.</p>
			<p><small>Small text for captions and metadata.</small></p>
			<blockquote><p>This is a blockquote. Styled with a gold left border and muted text.</p></blockquote>
		</section>

		<!-- Buttons -->
		<section class="style-guide-section">
			<h2 class="section-heading"><?php esc_html_e( 'Buttons', 'ddcwwfcsc-theme' ); ?></h2>
			<p>
				<a href="#" class="btn btn--primary">Primary Button</a>&nbsp;
				<a href="#" class="btn btn--ghost">Ghost Button</a>&nbsp;
				<a href="#" class="btn btn--primary btn--sm">Small Primary</a>&nbsp;
				<a href="#" class="btn btn--ghost btn--sm">Small Ghost</a>
			</p>
		</section>

		<!-- Badges -->
		<section class="style-guide-section">
			<h2 class="section-heading"><?php esc_html_e( 'Badges', 'ddcwwfcsc-theme' ); ?></h2>
			<p>
				<span class="badge badge--upcoming">Upcoming</span>
				<span class="badge badge--past">Past</span>
				<span class="badge badge--on-sale">On Sale</span>
				<span class="badge badge--sold-out">Sold Out</span>
			</p>
		</section>

		<!-- Forms -->
		<section class="style-guide-section">
			<h2 class="section-heading"><?php esc_html_e( 'Form Elements', 'ddcwwfcsc-theme' ); ?></h2>
			<form style="max-width:480px;" onsubmit="return false;">
				<p>
					<label for="sg-name"><?php esc_html_e( 'Name', 'ddcwwfcsc-theme' ); ?></label>
					<input type="text" id="sg-name" placeholder="Your name">
				</p>
				<p>
					<label for="sg-email"><?php esc_html_e( 'Email', 'ddcwwfcsc-theme' ); ?></label>
					<input type="email" id="sg-email" placeholder="you@example.com">
				</p>
				<p>
					<label for="sg-message"><?php esc_html_e( 'Message', 'ddcwwfcsc-theme' ); ?></label>
					<textarea id="sg-message" rows="4" placeholder="Your message"></textarea>
				</p>
				<p><button type="submit">Submit</button></p>
			</form>
		</section>

		<!-- Spacing -->
		<section class="style-guide-section">
			<h2 class="section-heading"><?php esc_html_e( 'Spacing Scale', 'ddcwwfcsc-theme' ); ?></h2>
			<div class="style-guide-spacing-demo">
				<div><div class="style-guide-spacing-block" style="width:var(--space-xs);height:var(--space-xs);"></div><small class="text-muted">xs</small></div>
				<div><div class="style-guide-spacing-block" style="width:var(--space-sm);height:var(--space-sm);"></div><small class="text-muted">sm</small></div>
				<div><div class="style-guide-spacing-block" style="width:var(--space-md);height:var(--space-md);"></div><small class="text-muted">md</small></div>
				<div><div class="style-guide-spacing-block" style="width:var(--space-lg);height:var(--space-lg);"></div><small class="text-muted">lg</small></div>
				<div><div class="style-guide-spacing-block" style="width:var(--space-xl);height:var(--space-xl);"></div><small class="text-muted">xl</small></div>
				<div><div class="style-guide-spacing-block" style="width:var(--space-2xl);height:var(--space-2xl);"></div><small class="text-muted">2xl</small></div>
				<div><div class="style-guide-spacing-block" style="width:var(--space-3xl);height:var(--space-3xl);"></div><small class="text-muted">3xl</small></div>
			</div>
		</section>

		<!-- Cards -->
		<section class="style-guide-section">
			<h2 class="section-heading"><?php esc_html_e( 'Cards', 'ddcwwfcsc-theme' ); ?></h2>
			<div class="grid grid--3">
				<div class="card">
					<div class="card__body">
						<h3 class="card__title"><a href="#">Card Title</a></h3>
						<p class="card__excerpt">A short excerpt that describes the card content in a few words.</p>
						<div class="card__meta">12 January 2026</div>
					</div>
				</div>
				<div class="event-card">
					<div class="event-card__date">Saturday 14 February 2026</div>
					<h3 class="event-card__title"><a href="#">Event Card</a></h3>
					<div class="event-card__location">The Dun Cow, Daventry</div>
					<div class="event-card__price">Members: &pound;10 &middot; Non-members: &pound;15</div>
				</div>
				<div class="beerwolf-archive-card">
					<div class="beerwolf-archive-card__body">
						<h3 class="beerwolf-archive-card__opponent"><a href="#">Arsenal</a></h3>
						<p class="beerwolf-archive-card__count">5 pubs</p>
					</div>
				</div>
			</div>
		</section>

	</div>
</main>

<?php
get_footer();
