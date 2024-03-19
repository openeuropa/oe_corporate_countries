<?php

namespace Drupal\Tests\oe_corporate_countries\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for creating content type and testing the exposed country filter.
 *
 * @group oe_corporate_countries
 */
class CorporateCountriesFilterTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'user',
    'views',
    'views_ui',
    'field',
    'address',
    'oe_corporate_countries',
    'oe_corporate_countries_test',
  ];

  /**
   * Theme to enable.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * A regular user with 'access content' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The test nodes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  /**
   * Administrator user for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Name of the field.
   *
   * Note, this is used in the default test view.
   *
   * @var string
   */
  protected $fieldName = 'field_country';

  /**
   * Title of France node.
   *
   * Note, this is used in the default test view.
   *
   * @var string
   */
  protected $nodeTitleFr = 'Test countries Node title France';

  /**
   * Title of Belgium node.
   *
   * Note, this is used in the default test view.
   *
   * @var string
   */
  protected $nodeTitleBe = 'Test countries Node title Belgium';

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['countries'];

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp():void {
    parent::setUp();

    $admin_permissions = [
      'administer views',
    ];

    // Create and log in user.
    $this->adminUser = $this->drupalCreateUser($admin_permissions);
    $this->drupalLogin($this->adminUser);

    // Create some nodes.
    $this->nodes = [];
    $this->nodes['FR'] = $this->drupalCreateNode([
      'type' => 'oe_countries',
      'status' => 1,
      'title' => $this->nodeTitleFr,
      $this->fieldName => [
        'value' => 'FR',
      ],
    ]);

    $this->nodes['BE'] = $this->drupalCreateNode([
      'type' => 'oe_countries',
      'status' => 1,
      'title' => $this->nodeTitleBe,
      $this->fieldName => [
        'value' => 'BE',
      ],
    ]);
  }

  /**
   * Test exposed filter options.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testExposedFilterOptions() {

    // Get countries view.
    $this->drupalGet('countries');
    $this->assertSession()->statusCodeEquals(200);

    // Test that countries exists on the filter.
    $countries_view_options = $this->getOptions('field_country_value');
    $this->assertEquals('Belgium', $countries_view_options['BE']);
    $this->assertEquals('France', $countries_view_options['FR']);

    // Test that page has France and Belgium nodes.
    $this->assertSession()->pageTextContains($this->nodeTitleBe);
    $this->assertSession()->pageTextContains($this->nodeTitleFr);

    // Test that deprecated countries do not exist on the filter.
    $this->assertArrayNotHasKey('AN', $countries_view_options);
    $this->assertArrayNotHasKey('ZR', $countries_view_options);
  }

  /**
   * Test exposed filter in views ui.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testExposedFilterViewsUi() {
    // Assert the page without filtering on views ui.
    $this->drupalGet('admin/structure/views/view/countries');
    $this->submitForm([], 'Update preview');
    $results = $this->cssSelect('.views-row');
    $this->assertCount(2, $results);
    $this->assertSession()->pageTextContains($this->nodeTitleFr);
    $this->assertSession()->pageTextContains($this->nodeTitleBe);

    // Assert button and filter exist on views ui.
    $page = $this->getSession()->getPage();
    $button = $page->findButton('Apply');
    $select = $page->findField('field_country_value');
    $this->assertNotEmpty($button);
    $this->assertNotEmpty($select);
  }

  /**
   * Test exposed filter on country page.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testExposedFilterPage() {
    // Assert FR returns FR Node using directly drupalGet.
    $options = ['query' => ['field_country_value' => 'FR']];
    $this->drupalGet('countries', $options);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->nodeTitleFr);
    $this->assertSession()->pageTextNotContains($this->nodeTitleBe);

    // Assert BE returns BE Node using click events.
    $this->drupalGet('countries');
    $this->assertSession()->statusCodeEquals(200);
    $this->getSession()->getPage()->selectFieldOption('field_country', 'BE');
    $this->getSession()->getPage()->pressButton('Apply');
    $url = $this->getSession()->getCurrentUrl();
    $this->drupalGet($url);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains($this->nodeTitleFr);
    $this->assertSession()->pageTextContains($this->nodeTitleBe);
  }

}
