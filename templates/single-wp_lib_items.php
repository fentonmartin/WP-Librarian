<?php
/*
 * Template Name: Library Single Item
 */

get_header();

?>
<div id="primary">
	<div id="content" role="main">
	<?php
	if (have_posts()) : while (have_posts()) : the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<div class="wp-lib-item-left">
					<header class="entry-header">
						<!-- Item title and meta -->
						<strong>Title:</strong> <?php the_title(); ?><br />
						<?php echo wp_lib_fetch_meta( get_the_ID() ); ?>
					</header>
					<!-- Item description -->
					<div class="entry-content"><?php the_content(); ?></div>
				</div>
				<div class="wp-lib-item-right">
					<!-- Item cover image -->
					<div class="wp-lib-item-cover">
						<?php the_post_thumbnail( array( 200, 500 ) ); ?>
					</div>
				</div>
			</article>
	<?php endwhile; else: endif; ?>
	</div>
</div>
<?php get_footer(); ?>