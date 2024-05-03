<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_corporate_countries_address\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\oe_corporate_countries\Kernel\CorporateCountriesRdfKernelTestBase;

/**
 * Tests the AvailableCountriesSubscriber class.
 */
class AvailableCountriesSubscriberTest extends CorporateCountriesRdfKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'entity_test',
    'address',
    'oe_corporate_countries_address',
  ];

  /**
   * Tests the deprecated countries are removed from the countries array.
   */
  public function testGetAvailableCountriesArray(): void {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_country',
      'entity_type' => 'entity_test',
      'type' => 'address_country',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
      'label' => 'My field',
    ])->save();

    // Get the actual countries from field.
    $entity = EntityTest::create();
    $actual_countries = $entity->get('field_country')
      ->appendItem()
      ->getAvailableCountries();

    // These are the expected countries we want to check for.
    $expected_countries = [
      'XK' => 'XK',
      'AX' => 'AX',
      'BE' => 'BE',
      'TF' => 'TF',
      'IT' => 'IT',
    ];

    // Assert that the actual countries array is correctly keyed.
    $this->assertEquals($expected_countries, $actual_countries);
    // Assert that the actual countries do not contain deprecated countries.
    $this->assertNotContains('AN', $actual_countries);

  }

}
