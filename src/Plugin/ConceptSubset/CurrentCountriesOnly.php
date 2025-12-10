<?php

declare(strict_types=1);

namespace Drupal\oe_corporate_countries\Plugin\ConceptSubset;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\rdf_skos\Plugin\PredicateMapperInterface;
use Drupal\sparql_entity_storage\SparqlEntityStorageFieldHandlerInterface;

/**
 * Subset of the countries vocabulary with countries only.
 *
 * @ConceptSubset(
 *   id = "countries_only",
 *   label = @Translation("Countries Only"),
 *   description = @Translation("Filters out territories and deprecated countries."),
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
class CurrentCountriesOnly extends NonDeprecatedCountries implements PredicateMapperInterface {

  /**
   * {@inheritdoc}
   */
  public function alterQuery(QueryInterface $query, $match_operator, array $concept_schemes = [], ?string $match = NULL): void {
    // Filter out deprecated countries first.
    parent::alterQuery($query, $match_operator, $concept_schemes, $match);
    // Then filter by countries only.
    $query->condition('countries_context', 'http://publications.europa.eu/resource/authority/use-context/COUNTRY');
    // Exclude the 'Data Provisions' entry by workaround, as expected usage with
    // context and filtering with AND logic by
    // 'http://publications.europa.eu/resource/authority/use-context/COM_WEB'
    // does not work due to 'Virtuoso 42000 Error The estimated execution time 0
    // (sec) exceeds the limit of ...' error. For fixing described issue needs
    // to optimize the SPARQL query generation in the SparqlEntityStorage
    // module.
    // @todo Optimize SPARQL query generation to remove this workaround.
    $query->condition('id', 'http://publications.europa.eu/resource/authority/country/OP_DATPRO', '!=');

  }

  /**
   * {@inheritdoc}
   */
  public function getPredicateMapping(): array {
    $mapping = [];

    $mapping['countries_context'] = [
      'column' => 'value',
      'predicate' => ['http://lemon-model.net/lemon#context'],
      'format' => SparqlEntityStorageFieldHandlerInterface::RESOURCE,
    ];

    return $mapping;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFieldDefinitions(): array {
    $fields = [];

    $fields['countries_context'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Context'))
      ->setDescription(t('Context in which a term is to be used.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    return $fields;
  }

}
