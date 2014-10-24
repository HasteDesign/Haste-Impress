<?php include( plugin_dir_path( __FILE__ ) . 'header-impress.php' ); ?>
	<!--
		Now that's the core element used by impress.js.

		That's the wrapper for your presentation steps. In this element all the impress.js magic happens.
		It doesn't have to be a `<div>`. Only `id` is important here as that's how the script find it.

		You probably won't need it now, but there are some configuration options that can be set on this element.

		To change the duration of the transition between slides use `data-transition-duration="2000"` giving it
		a number of ms. It defaults to 1000 (1s).

		You can also control the perspective with `data-perspective="500"` giving it a number of pixels.
		It defaults to 1000. You can set it to 0 if you don't want any 3D effects.
		If you are willing to change this value make sure you understand how CSS perspective works:
		https://developer.mozilla.org/en/CSS/perspective

		But as I said, you won't need it for now, so don't worry - there are some simple but interesting things
		right around the corner of this tag ;)
	-->
	<div id="impress">
		<?php
		if( have_posts() ) {
			while( have_posts() ) {
				the_post();

				//Step Attributtes
				$head_text = get_post_meta( $post->ID, 'head-text', true );
				$data_x = get_post_meta( $post->ID, 'data-x', true );
				$data_y = get_post_meta( $post->ID, 'data-y', true );
				$data_z = get_post_meta( $post->ID, 'data-z', true );
				$data_rotate_x = get_post_meta( $post->ID, 'data-rotate-x', true );
				$data_rotate_y = get_post_meta( $post->ID, 'data-rotate-y', true );
				$data_rotate_z = get_post_meta( $post->ID, 'data-rotate-z', true );
				$data_scale = get_post_meta( $post->ID, 'data-scale', true );
				$step_class = get_post_meta( $post->ID, 'step-class', true );
				$step_format = get_post_meta( $post->ID, 'step-format', true );
				$empty = get_post_meta( $post->ID, 'step-empty', true );

				if( empty( $empty ) ){
					if( !empty( $step_format ) ) {
						include( plugin_dir_path( __FILE__ ) . 'content-' . $step_format . '.php' );
					}
				} else {
						include( plugin_dir_path( __FILE__ ) . 'content-empty.php' );
				}
			}
		} else {
			echo 'Sorry, no presentation found.';
		}
		?>
	</div><!-- #impress -->

	<!--

		Hint is not related to impress.js in any way.

		But it can show you how to use impress.js features in creative way.

		When the presentation step is shown (selected) its element gets the class of "active" and the body element
		gets the class based on active step id `impress-on-ID` (where ID is the step's id)... It may not be
		so clear because of all these "ids" in previous sentence, so for example when the first step (the one with
		the id of `bored`) is active, body element gets a class of `impress-on-bored`.

		This class is used by this hint below. Check CSS file to see how it's shown with delayed CSS animation when
		the first step of presentation is visible for a couple of seconds.

		...

		And when it comes to this piece of JavaScript below ... kids, don't do this at home ;)
		It's just a quick and dirty workaround to get different hint text for touch devices.
		In a real world it should be at least placed in separate JS file ... and the touch content should be
		probably just hidden somewhere in HTML - not hard-coded in the script.

		Just sayin' ;)

	-->

	<script>
	if ("ontouchstart" in document.documentElement) {
		document.querySelector(".hint").innerHTML = "<p>Tap on the left or right to navigate</p>";
	}
	</script>

	<!--

		Last, but not least.

		To make all described above really work, you need to include impress.js in the page.
		I strongly encourage to minify it first.

		In here I just include full source of the script to make it more readable.

		You also need to call a `impress().init()` function to initialize impress.js presentation.
		And you should do it in the end of your document. Not only because it's a good practice, but also
		because it should be done when the whole document is ready.
		Of course you can wrap it in any kind of "DOM ready" event, but I was too lazy to do so ;)

	-->
	<!--

		The `impress()` function also gives you access to the API that controls the presentation.

		Just store the result of the call:

			var api = impress();

		and you will get three functions you can call:

			`api.init()` - initializes the presentation,
			`api.next()` - moves to next step of the presentation,
			`api.prev()` - moves to previous step of the presentation,
			`api.goto( idx | id | element, [duration] )` - moves the presentation to the step given by its index number
					id or the DOM element; second parameter can be used to define duration of the transition in ms,
					but it's optional - if not provided default transition duration for the presentation will be used.

		You can also simply call `impress()` again to get the API, so `impress().next()` is also allowed.
		Don't worry, it wont initialize the presentation again.

		For some example uses of this API check the last part of the source of impress.js where the API
		is used in event handlers.

	-->
<?php include( plugin_dir_path( __FILE__ ) . 'footer-impress.php' ); ?>
