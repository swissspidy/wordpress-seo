<?php
/**
 * WPSEO plugin file.
 *
 * @package Yoast\WP\Free\Presentations\Generators\Schema
 */

namespace Yoast\WP\Free\Presentations\Generators\Schema;

use WP_Post;
use Yoast\WP\Free\Context\Meta_Tags_Context;
use Yoast\WP\Free\Helpers\Current_Page_Helper;

/**
 * Returns schema WebPage data.
 *
 * @since 10.2
 */
class WebPage extends Abstract_Schema_Piece {

	/**
	 * @var Current_Page_Helper
	 */
	private $current_page_helper;

	/**
	 * WebPage constructor.
	 *
	 * @param Current_Page_Helper $current_page_helper
	 */
	public function __construct( Current_Page_Helper $current_page_helper ) {
		$this->current_page_helper = $current_page_helper;
	}

	/**
	 * Determines whether or not a piece should be added to the graph.
	 *
	 * @param Meta_Tags_Context $context The meta tags context.
	 *
	 * @return bool
	 */
	public function is_needed( Meta_Tags_Context $context ) {
		return $context->indexable->object_type !== 'error-page';
	}

	/**
	 * Returns WebPage schema data.
	 *
	 * @return array WebPage schema data.
	 */
	public function generate( Meta_Tags_Context $context ) {
		$data = [
			'@type'      => $context->schema_page_type,
			'@id'        => $context->canonical . $this->id_helper->webpage_hash,
			'url'        => $context->canonical,
			'inLanguage' => \get_bloginfo( 'language' ),
			'name'       => $context->title,
			'isPartOf'   => [
				'@id' => $context->site_url . $this->id_helper->website_hash,
			],
		];

		if ( \is_front_page() ) {
			if ( $context->site_represents_reference ) {
				$data['about'] = $context->site_represents_reference;
			}
		}

		if ( $context->indexable->object_type === 'post' ) {
			$this->add_image( $data, $context );

			$data['datePublished'] = \mysql2date( DATE_W3C, $context->post->post_date_gmt, false );
			$data['dateModified']  = \mysql2date( DATE_W3C, $context->post->post_modified_gmt, false );

			if ( $context->indexable->object_sub_type === 'post' ) {
				$data = $this->add_author( $data, $context->post, $context );
			}
		}

		if ( ! empty( $context->description ) ) {
			$data['description'] = \strip_tags( $context->description, '<h1><h2><h3><h4><h5><h6><br><ol><ul><li><a><p><b><strong><i><em>' );
		}

		if ( $this->add_breadcrumbs( $context ) ) {
			$data['breadcrumb'] = array(
				'@id' => $context->canonical . $this->id_helper->breadcrumb_hash,
			);
		}

		return $data;
	}

	/**
	 * Adds an author property to the $data if the WebPage is not represented.
	 *
	 * @param array             $data    The WebPage schema.
	 * @param WP_Post           $post    The post the context is representing.
	 * @param Meta_Tags_Context $context The meta tags context.
	 *
	 * @return array The WebPage schema.
	 */
	public function add_author( $data, $post, Meta_Tags_Context $context ) {
		if ( $context->site_represents === false ) {
			$data['author'] = [ '@id' => $this->id_helper->get_user_schema_id( $post->post_author, $context ) ];
		}

		return $data;
	}

	/**
	 * If we have an image, make it the primary image of the page.
	 *
	 * @param array             $data    WebPage schema data.
	 * @param Meta_Tags_Context $context The meta tags context.
	 */
	public function add_image( &$data, Meta_Tags_Context $context ) {
		if ( $context->has_image ) {
			$data['primaryImageOfPage'] = [ '@id' => $context->canonical . $this->id_helper->primary_image_hash ];
		}
	}

	/**
	 * Determine if we should add a breadcrumb attribute.
	 *
	 * @param Meta_Tags_Context $context The meta tags context.
	 *
	 * @return bool
	 */
	private function add_breadcrumbs( Meta_Tags_Context $context ) {
		if ( $context->indexable->object_type === 'home-page' || $this->current_page_helper->is_home_static_page() ) {
			return false;
		}

		if ( $context->breadcrumbs_enabled ) {
			return true;
		}

		return false;
	}
}
