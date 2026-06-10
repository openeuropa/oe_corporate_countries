<?php

declare(strict_types=1);

namespace Drupal\oe_corporate_countries;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface;
use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlArg;
use Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface;

/**
 * A corporate country repository implementation.
 */
class CorporateCountryRepository implements CorporateCountryRepositoryInterface {

  /**
   * The graph handler.
   *
   * @var \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface
   */
  protected $graphHandler;

  /**
   * The SPARQL database connection.
   *
   * @var \Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface
   */
  protected $sparql;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * Instantiates a new CorporateCountryRepository object.
   *
   * @param \Drupal\sparql_entity_storage\SparqlEntityStorageGraphHandlerInterface $graphHandler
   *   The graph handler service.
   * @param \Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface $sparql
   *   The SPARQL database connection.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   The module extension list.
   */
  public function __construct(SparqlEntityStorageGraphHandlerInterface $graphHandler, ConnectionInterface $sparql, ModuleExtensionList $moduleExtensionList) {
    $this->graphHandler = $graphHandler;
    $this->sparql = $sparql;
    $this->moduleExtensionList = $moduleExtensionList;
  }

  /**
   * {@inheritdoc}
   */
  public function getCountries(): array {
    // @todo this function is heavily invoked and should be cached.
    $graphs = $this->graphHandler->getEntityTypeGraphUrisFlatList('skos_concept');
    // If no graphs are specified, no countries can be retrieved.
    if (empty($graphs)) {
      return [];
    }

    $from_graphs = '';
    foreach ($graphs as $graph_uri) {
      $from_graphs .= sprintf("FROM %s\n", SparqlArg::uri($graph_uri));
    }

    $query = <<<SPARQL
SELECT DISTINCT ?id ?authcode ?deprecated
$from_graphs
WHERE {
  ?id <http://www.w3.org/2004/02/skos/core#inScheme> <http://publications.europa.eu/resource/authority/country> .
  ?id a <http://www.w3.org/2004/02/skos/core#Concept> .
  ?id <http://publications.europa.eu/ontology/authority/authority-code> ?authcode .
  ?id <http://publications.europa.eu/ontology/authority/deprecated> ?deprecated .
}
ORDER BY ASC(?authcode)
SPARQL;

    $results = $this->sparql->query($query);

    $code_mappings = $this->getIsoCodeMappings();
    $countries = [];
    foreach ($results as $item) {
      $auth_code = $item->authcode->getValue();
      // If no alpha-2 code is present, skip the value.
      if (!isset($code_mappings[$auth_code])) {
        continue;
      }

      $countries[$item->id->getUri()] = [
        'alpha-2' => $code_mappings[$auth_code],
        'authority_code' => $auth_code,
        // The deprecated value is returned as string, so apply the same
        // conversion done in \EasyRdf\Literal\Boolean::isTrue().
        'deprecated' => $item->deprecated->getValue() === 'true' || $item->deprecated->getValue() === '1',
      ];
    }

    return $countries;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeprecatedCountries(): array {
    return array_filter($this->getCountries(), function ($data): bool {
      return $data['deprecated'];
    });
  }

  /**
   * {@inheritdoc}
   */
  public function getCountryByIsoAlpha2(string $alpha2): ?array {
    foreach ($this->getCountries() as $id => $data) {
      if ($data['alpha-2'] === $alpha2) {
        // Add the country SKOS concept ID in the returned data.
        $data['id'] = $id;
        return $data;
      }
    }

    return NULL;
  }

  /**
   * Returns a mapping between ISO 3166-1 alpha-2 and OP country codes.
   *
   * @return array
   *   The alpha-2 codes, keyed by OP authority code.
   */
  protected function getIsoCodeMappings(): array {
    $filename = $this->moduleExtensionList->getPath('oe_corporate_countries') . '/resources/country-code-mappings.json';
    $code_mappings = Json::decode(file_get_contents($filename));

    return is_array($code_mappings) ? $code_mappings : [];
  }

}
