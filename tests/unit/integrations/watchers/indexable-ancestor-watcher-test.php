<?php
/**
 * WPSEO plugin test file.
 *
 * @package Yoast\WP\SEO\Tests\Unit\Integrations\Watchers
 */

namespace Yoast\WP\SEO\Tests\Unit\Integrations\Watchers;

use Mockery;
use Yoast\WP\SEO\Conditionals\Migrations_Conditional;
use Yoast\WP\SEO\Integrations\Watchers\Indexable_Ancestor_Watcher;
use Yoast\WP\SEO\Repositories\Indexable_Repository;
use Yoast\WP\SEO\Tests\Unit\Doubles\Models\Indexable_Mock;
use Yoast\WP\SEO\Tests\Unit\TestCase;

/**
 * Class Indexable_Ancestor_Watcher_Test.
 *
 * @group indexables
 * @group integrations
 * @group watchers
 *
 * @coversDefaultClass \Yoast\WP\SEO\Integrations\Watchers\Indexable_Ancestor_Watcher
 * @covers ::<!public>
 */
class Indexable_Ancestor_Watcher_Test extends TestCase {

	/**
	 * Represents the indexable repository.
	 *
	 * @var Mockery\MockInterface|Indexable_Repository
	 */
	protected $indexable_repository;

	/**
	 * Represents the instance to test.
	 *
	 * @var Indexable_Ancestor_Watcher
	 */
	protected $instance;

	/**
	 * @inheritDoc
	 */
	public function setUp() {
		parent::setUp();

		$this->indexable_repository = Mockery::mock( Indexable_Repository::class );
		$this->instance             = new Indexable_Ancestor_Watcher( $this->indexable_repository );
	}

	/**
	 * Tests the clear ancestors method when the object type is not a post or term.
	 *
	 * @covers ::clear_ancestors
	 */
	public function test_clear_ancestors_for_non_allowed_object_type() {
		$indexable        = Mockery::mock( Indexable_Mock::class );
		$indexable_before = Mockery::mock( Indexable_Mock::class );

		$indexable->object_type = 'user';

		$this->assertFalse( $this->instance->clear_ancestors( $indexable, $indexable_before ) );
	}

	/**
	 * Tests the clear ancestors method having the permalink not changed.
	 *
	 * @covers ::clear_ancestors
	 */
	public function test_clear_ancestors_for_non_changed_permalink() {
		$indexable        = Mockery::mock( Indexable_Mock::class );
		$indexable_before = Mockery::mock( Indexable_Mock::class );

		$indexable->permalink        = 'https://example.org/permalink';
		$indexable_before->permalink = 'https://example.org/permalink';

		$indexable->object_type = 'post';

		$this->assertFalse( $this->instance->clear_ancestors( $indexable, $indexable_before ) );
	}

	/**
	 * Tests if the dependencies are set as expected.
	 *
	 * @covers ::__construct
	 */
	public function test_construct() {
		$this->assertAttributeInstanceOf( Indexable_Repository::class, 'indexable_repository', $this->instance );
	}

	/**
	 * Tests if the expected conditionals are in place.
	 *
	 * @covers ::get_conditionals
	 */
	public function test_get_conditionals() {
		$this->assertEquals(
			[ Migrations_Conditional::class ],
			Indexable_Ancestor_Watcher::get_conditionals()
		);
	}

	/**
	 * Tests if the expected hooks are registered.
	 *
	 * @covers ::register_hooks
	 */
	public function test_register_hooks() {
		$this->instance->register_hooks();

		$this->assertNotFalse( \has_action( 'wpseo_save_indexable', [ $this->instance, 'clear_ancestors' ] ) );
	}

	/**
	 * Tests the clear ancestors method.
	 *
	 * @covers ::clear_ancestors
	 */
	public function test_clear_ancestors() {
		$indexable        = Mockery::mock( Indexable_Mock::class );
		$indexable_before = Mockery::mock( Indexable_Mock::class );

		$indexable->permalink        = 'https://example.org/permalink';
		$indexable_before->permalink = 'https://example.org/old-permalink';

		$indexable->object_type = 'post';

		$child_indexable                 = Mockery::mock( Indexable_Mock::class );
		$child_indexable->permalink      = 'https://example.org/child-permalink';
		$child_indexable->permalink_hash = 'hash';
		$child_indexable->expects( 'save' )->once();

		$this->indexable_repository
			->expects( 'get_children' )
			->once()
			->with( $indexable )
			->andReturn( [ $child_indexable ] );

		$this->assertTrue( $this->instance->clear_ancestors( $indexable, $indexable_before ) );
	}

}
