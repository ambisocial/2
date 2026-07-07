<?php

	if( tie_get_postdata( 'tie_post_sub_title' ) ) { ?>
		<h2 class="amp-wp-sub-title"><?php echo apply_filters( 'TieLabs/Post/subtitle', tie_get_postdata( 'tie_post_sub_title', false, $this->ID ) ); ?></h2>
		<?php
	}
