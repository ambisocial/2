<?php

$post_format = tie_get_postdata( 'tie_post_head', 'standard' );

if( $post_format ){

  echo '<div class="amp-featured">';

  // Get the post video
  if( $post_format == 'video' ){

    echo tie_video();
  }

  // Get post audio
  elseif( $post_format == 'audio' ){

    tie_audio();
  }

  // Get post map
  elseif( $post_format == 'map' ){
    echo tie_google_maps( tie_get_postdata( 'tie_googlemap_url' ));
  }

  // Get post slider
  elseif( $post_format == 'slider' ){

    // Custom slider
    if( tie_get_postdata( 'tie_post_slider' ) ) {
      $slider     = tie_get_postdata( 'tie_post_slider' );
      $get_slider = get_post_custom( $slider );

      if( ! empty( $get_slider['custom_slider'][0] ) ){
        $images = maybe_unserialize( $get_slider['custom_slider'][0] );
      }
    }

    // Uploaded images
    elseif( tie_get_postdata( 'tie_post_gallery' ) ) {
      $images = maybe_unserialize( tie_get_postdata( 'tie_post_gallery' ));
    }

    $ids = array();
    if( ! empty( $images ) && is_array( $images ) ){
      foreach( $images as $single_image ){
        $ids[] = $single_image['id'];
      }
    }

    echo( do_shortcode('[gallery ids="'. implode( ',', $ids ) .'"]') );
  }

  // Featured Image
  elseif( has_post_thumbnail() && ( $post_format == 'thumb' ||
        ( $post_format == 'standard' && ( tie_get_object_option( 'post_featured', 'cat_post_featured', 'tie_post_featured' ) && tie_get_object_option( 'post_featured', 'cat_post_featured', 'tie_post_featured' ) != 'no' ) ) ) ) {

    the_post_thumbnail();

    // Featured image caption
    $thumb_caption = get_post( get_post_thumbnail_id() );
    if( ! empty( $thumb_caption->post_excerpt ) ) {
      echo '<figcaption class="single-caption-text">'. $thumb_caption->post_excerpt .'</figcaption>';
    }
  }

  echo '</div>';
}

