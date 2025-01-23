<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_corporate_countries_address\Kernel;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\oe_corporate_countries\CorporateCountryRepositoryInterface;
use Drupal\rdf_skos\Entity\ConceptInterface;
use Drupal\Tests\oe_corporate_countries\Kernel\CorporateCountriesRdfKernelTestBase;
use PHPUnit\Framework\Constraint\IsInstanceOf;

/**
 * Tests the country repository service.
 */
class CountryRepositoryTest extends CorporateCountriesRdfKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'address',
    'oe_corporate_countries_address',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('configurable_language');
    $this->installConfig(['language']);
    // At least one extra language needs to be enabled for translations to work.
    ConfigurableLanguage::createFromLangcode('it')->save();
  }

  /**
   * Tests that retrieval of country lists in different locales.
   */
  public function testGetList(): void {
    $repository = $this->container->get('address.country_repository');

    // The test country RDF contains 6 countries, but one is not mapped in the
    // country code mappings file, so only 5 should be returned.
    // When no locale is passed, the English labels are returned.
    $this->assertSame([
      'AX' => 'Åland Islands',
      'BE' => 'Belgium',
      'TF' => 'French Southern and Antarctic Lands',
      'IT' => 'Italy',
      'XK' => 'Kosovo',
      'AN' => 'Netherlands Antilles',
    ], $repository->getList());

    // Test that the correct translated labels are returned when a specific
    // language is passed.
    $this->assertSame([
      'AN' => 'Antille olandesi',
      'BE' => 'Belgio',
      'AX' => 'Isole Åland',
      'IT' => 'Italia',
      'XK' => 'Kosovo',
      'TF' => 'Terre australi e antartiche francesi',
    ], $repository->getList('IT'));

    // When the language passed doesn't exist, the English labels are returned.
    $this->assertSame([
      'AX' => 'Åland Islands',
      'BE' => 'Belgium',
      'TF' => 'French Southern and Antarctic Lands',
      'IT' => 'Italy',
      'XK' => 'Kosovo',
      'AN' => 'Netherlands Antilles',
    ], $repository->getList('es'));
  }

  /**
   * Tests the definition caching.
   */
  public function testLoadDefinitionsCache(): void {
    $corporate_repository_mock = $this->createMock(CorporateCountryRepositoryInterface::class);
    $corporate_repository_mock
      ->expects($this->once())
      ->method('getCountries')
      ->willReturn([]);
    $this->container->set('oe_corporate_countries.corporate_country_repository', $corporate_repository_mock);

    $country_repository = $this->container->get('address.country_repository');
    $country_repository->getList();
    // The service has a local property where the list is cached.
    $country_repository->getList();
    // Invalidate the service so the local cache of the method is not present
    // and the cache service is used.
    $this->container->set('address.country_repository', NULL);
    $this->container->get('address.country_repository')->getList();
  }

  /**
   * Tests the definitions cache invalidation by tag.
   */
  public function testLoadDefinitionsCacheInvalidation(): void {
    $corporate_repository_mock = $this->createMock(CorporateCountryRepositoryInterface::class);
    $corporate_repository_mock
      ->expects($this->exactly(2))
      ->method('getCountries')
      ->willReturn([]);
    $this->container->set('oe_corporate_countries.corporate_country_repository', $corporate_repository_mock);

    $country_repository = $this->container->get('address.country_repository');
    $country_repository->getList();
    $country_repository->getList('en');
    // Invalidate the service so the local cache is not present.
    $this->container->set('address.country_repository', NULL);
    // Invalidate the cache entry.
    $this->container->get('cache_tags.invalidator')->invalidateTags(['countries']);
    // Now the corporate repository method will be invoked again.
    $this->container->get('address.country_repository')->getList();
  }

  /**
   * Tests that the definitions are cached by language.
   */
  public function testLoadDefinitionsCacheByLanguage(): void {
    // Limit the corporate countries to only one, to ease the setup of test
    // expectations.
    $corporate_repository_mock = $this->createMock(CorporateCountryRepositoryInterface::class);
    $corporate_repository_mock
      ->method('getCountries')
      ->willReturn([
        'http://publications.europa.eu/resource/authority/country/ITA' => ['alpha-2' => 'IT'],
      ]);
    $this->container->set('oe_corporate_countries.corporate_country_repository', $corporate_repository_mock);

    // Mock the entity repository so we can assert that the expected langcode
    // is passed to it and that the loading is cached properly.
    $entity_repository_mock = $this->createMock(EntityRepositoryInterface::class);
    $entity_repository_mock
      ->expects($this->exactly(2))
      ->method('getTranslationFromContext')
      ->willReturnOnConsecutiveCalls(
        [new IsInstanceOf(ConceptInterface::class), 'en'],
        [new IsInstanceOf(ConceptInterface::class), 'fr']
      )
      ->willReturnArgument(0);
    $this->container->set('entity.repository', $entity_repository_mock);

    $this->container->get('address.country_repository')->getList();
    // Invalidate the service so the local cache is not present.
    $this->container->set('address.country_repository', NULL);
    // This call will be cached.
    $this->container->get('address.country_repository')->getList('en');
    // This call won't be cached and it will hit the second parameter set.
    $this->container->set('address.country_repository', NULL);
    $this->container->get('address.country_repository')->getList('fr');
    // This call will be cached.
    $this->container->set('address.country_repository', NULL);
    $this->container->get('address.country_repository')->getList('fr');
    // Previous entries are still cached.
    $this->container->set('address.country_repository', NULL);
    $this->container->get('address.country_repository')->getList('en');
  }

}
