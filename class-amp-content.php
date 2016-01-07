<?php

require_once( dirname( __FILE__ ) . '/includes/class-amp-dom-utils.php' );

require_once( dirname( __FILE__ ) . '/includes/sanitizers/class-amp-base-sanitizer.php' );
require_once( dirname( __FILE__ ) . '/includes/sanitizers/class-amp-blacklist-sanitizer.php' );
require_once( dirname( __FILE__ ) . '/includes/sanitizers/class-amp-img-sanitizer.php' );

require_once( dirname( __FILE__ ) . '/class-amp-iframe.php' );
require_once( dirname( __FILE__ ) . '/class-amp-video.php' );
require_once( dirname( __FILE__ ) . '/class-amp-audio.php' );
require_once( dirname( __FILE__ ) . '/class-amp-embed-handler.php' );
require_once( dirname( __FILE__ ) . '/class-amp-twitter-embed.php' );
require_once( dirname( __FILE__ ) . '/class-amp-youtube-embed.php' );
require_once( dirname( __FILE__ ) . '/class-amp-gallery-embed.php' );

class AMP_Content {
	private $original_content;
	private $scripts;

	public function __construct( $content ) {
		$this->original_content = $content;
		$this->scripts = array();
	}

	public function transform() {
		$content = $this->original_content;

		$twitter_embed = new AMP_Twitter_Embed_Handler;
		$youtube_embed = new AMP_YouTube_Embed_Handler;
		$gallery_embed = new AMP_Gallery_Embed_Handler;
		$content = apply_filters( 'the_content', $content );
		$this->add_scripts( $twitter_embed->get_scripts() );
		$this->add_scripts( $youtube_embed->get_scripts() );
		$this->add_scripts( $gallery_embed->get_scripts() );

		$dom = AMP_DOM_Utils::get_dom_from_content( $content );

		$this->sanitize( new AMP_Blacklist_Sanitizer( $dom ) );

		$this->sanitize( new AMP_Img_Sanitizer( $dom ), array(
			'layout' => 'responsive',
		) );

		$content = AMP_DOM_Utils::get_content_from_dom( $dom );

		$content = $this->convert( new AMP_Video_Converter( $content ), array(
			'layout' => 'responsive',
		) );

		$content = $this->convert( new AMP_Audio_Converter( $content ), array(
			'layout' => 'responsive',
		) );

		$content = $this->convert( new AMP_Iframe_Converter( $content ), array(
			'layout' => 'responsive',
		) );

		return $content;
	}

	public function add_scripts( $scripts ) {
		$this->scripts = array_merge( $this->scripts, $scripts );
	}

	public function get_scripts() {
		return $this->scripts;
	}

	private function sanitize( $sanitizer, $attributes = array() ) {
		$sanitizer->sanitize( $attributes );
		$this->add_scripts( $sanitizer->get_scripts() );
	}

	private function convert( $converter, $attributes ) {
		$converted = $converter->convert( $attributes );
		$this->add_scripts( $converter->get_scripts() );
		return $converted;
	}
}
