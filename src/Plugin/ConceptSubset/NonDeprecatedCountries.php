<?php

declare(strict_types=1);

namespace Drupal\oe_corporate_countries\Plugin\ConceptSubset;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rdf_skos\ConceptSubsetPluginBase;
use Drupal\rdf_skos\Plugin\PredicateMapperInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandlerInterface;

/**
 * Subset of the countries vocabulary with non-deprecated countries only.
 *
 * @ConceptSubset(
 *   id = "non_deprecated_countries",
 *   label = @Translation("Non-deprecated countries"),
 *   description = @Translation("Filters out deprecated countries."),
 *   predicate_mapping = TRUE,
 *   concept_schemes = {
 *     "http://publications.europa.eu/resource/authority/country",
 *     "http://publications.europa.eu/resource/authority/country/0001",
 *     "http://publications.europa.eu/resource/authority/country/0002",
 *     "http://publications.europa.eu/resource/authority/country/0003",
 *     "http://publications.europa.eu/resource/authority/country/0004",
 *     "http://publications.europa.eu/resource/authority/country/0005",
 *     "http://publications.europa.eu/resource/authority/country/0006",
 *     "http://publications.europa.eu/resource/authority/country/0007",
 *     "http://publications.europa.eu/resource/authority/country/0008",
 *     "http://publications.europa.eu/resource/authority/country/0009",
 *     "http://publications.europa.eu/resource/authority/country/0010"
 *   }
 * )
 */
class NonDeprecatedCountries extends ConceptSubsetPluginBase implements PredicateMapperInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function alterQuery(QueryInterface $query, $match_operator, array $concept_schemes = [], ?string $match = NULL): void {
    $query->condition('deprecated', ['false', '0'], 'IN');
  }

  /**
   * {@inheritdoc}
   */
  public function getPredicateMapping(): array {
    $mapping = [];

    $mapping['deprecated'] = [
      'column' => 'value',
      'predicate' => ['http://publications.europa.eu/ontology/authority/deprecated'],
      'format' => SparqlEntityStorageFieldHandlerInterface::NON_TYPE,
    ];

    return $mapping;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFieldDefinitions(): array {
    $fields = [];

    $fields['deprecated'] = BaseFieldDefinition::create('string')
      ->setLabel($this->t('Deprecated'))
      ->setDescription($this->t('Whether the country is deprecated or not.'))
      ->setCardinality(1);

    return $fields;
  }

}
