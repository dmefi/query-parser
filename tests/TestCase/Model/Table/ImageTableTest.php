<?php
namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ImageTable;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ImageTable Test Case
 */
class ImageTableTest extends TestCase
{

    /**
     * Test subject
     *
     * @var \App\Model\Table\ImageTable
     */
    public $Image;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.image'
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $config = TableRegistry::exists('Image') ? [] : ['className' => ImageTable::class];
        $this->Image = TableRegistry::get('Image', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Image);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
