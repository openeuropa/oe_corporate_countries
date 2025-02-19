<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_corporate_countries_address\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\oe_corporate_countries\Kernel\CorporateCountriesRdfKernelTestBase;

/**
 * Tests constrains on field types that contain countries.
 */
class FieldConstraintValidationTest extends CorporateCountriesRdfKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'address',
    'oe_corporate_countries_address',
    'entity_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');

    $this->container->get('module_handler')->loadInclude('user', 'install');
    user_install();
  }

  /**
   * Tests that the deprecation constraint is applied to the country field type.
   */
  public function testCountryFieldTypeDeprecationConstraint(): void {
    $this->createField('address_country');

    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = EntityTest::create();
    // When no country value is present, there are no validation errors.
    $this->assertCount(0, $entity->validate());

    // Setting an invalid country will trigger a validation error.
    $entity->set('field_test', 'XY');
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('The country <em class="placeholder">&quot;XY&quot;</em> is not valid.', (string) $violations[0]->getMessage());
    $this->assertEquals('field_test.0.value', $violations[0]->getPropertyPath());

    // Setting a deprecated country will trigger a deprecation error.
    $entity->set('field_test', 'AN');
    $violations = $entity->validate();
    $this->assertCount(2, $violations);
    $this->assertEquals('The country "<em class="placeholder">AN</em>" is deprecated. Please specify a replacement.', (string) $violations[0]->getMessage());
    $this->assertEquals('field_test.0.value', $violations[0]->getPropertyPath());
    $this->assertEquals('The country <em class="placeholder">&quot;AN&quot;</em> is not available.', (string) $violations[1]->getMessage());
    $this->assertEquals('field_test.0.value', $violations[0]->getPropertyPath());

    // Valid country values do not trigger any error.
    $entity->set('field_test', 'IT');
    $this->assertCount(0, $entity->validate());
  }

  /**
   * Tests that the deprecation constraint is applied to the address field type.
   */
  public function testAddressFieldTypeDeprecationConstraint(): void {
    $this->createField('address');

    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = EntityTest::create();
    // When no country value is present, there are no validation errors.
    $this->assertCount(0, $entity->validate());

    // Setting an invalid country will trigger a validation error.
    $entity->set('field_test', [
      'country_code' => 'XY',
      'address_line1' => 'Via Sardegna',
      'locality' => 'Roma',
      'given_name' => 'Mario',
      'family_name' => 'Rossi',
    ]);
    $violations = $entity->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('The country <em class="placeholder">&quot;XY&quot;</em> is not valid.', (string) $violations[0]->getMessage());
    $this->assertEquals('field_test.0.country_code', $violations[0]->getPropertyPath());

    // Setting a deprecated country will trigger a deprecation error.
    $entity->set('field_test', [
      'country_code' => 'AN',
      'address_line1' => 'Via Sardegna',
      'locality' => 'Roma',
      'given_name' => 'Mario',
      'family_name' => 'Rossi',
    ]);
    $violations = $entity->validate();
    $this->assertCount(2, $violations);
    $this->assertEquals('The country "<em class="placeholder">AN</em>" is deprecated. Please specify a replacement.', (string) $violations[0]->getMessage());
    $this->assertEquals('field_test.0.country_code', $violations[0]->getPropertyPath());
    $this->assertEquals('The country <em class="placeholder">&quot;AN&quot;</em> is not available.', (string) $violations[1]->getMessage());
    $this->assertEquals('field_test.0.country_code', $violations[0]->getPropertyPath());

    // Valid country values do not trigger any error.
    $entity->set('field_test', [
      'country_code' => 'IT',
      'address_line1' => 'Via Sardegna',
      'locality' => 'Roma',
      'administrative_area' => 'RM',
      'postal_code' => '00100',
      'given_name' => 'Mario',
      'family_name' => 'Rossi',
    ]);
    $this->assertCount(0, $entity->validate());
  }

  /**
   * Creates an entity_test field of the given type.
   *
   * @param string $field_type
   *   The field type.
   */
  protected function createField(string $field_type): void {
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => $field_type,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
      'label' => $this->randomMachineName(),
    ]);
    $field->save();
  }

}
