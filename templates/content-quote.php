	<?php include( plugin_dir_path( __FILE__ ) . 'slide-parameters.php' ); ?>

	<?php if( !empty( $head_text ) ) { ?>
		<p class="head-text"><?php echo $head_text ?></p>
	<?php }?>

	<?php the_content(); ?>
</div>
