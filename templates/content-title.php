	<?php include( dirname( __FILE__ ) . '/slide-parameters.php' ); ?>

	<?php if( !empty( $head_text ) ) { ?>
		<p class="head-text"><?php echo $head_text ?></p>
	<?php }?>
	<?php the_title('<h1>', '</h1>'); ?>
	<?php the_content(); ?>
</div>
