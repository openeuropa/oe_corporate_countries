<?php

declare(strict_types = 1);

namespace Drupal\oe_corporate_countries_address\EventSubscriber;

use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\AvailableCountriesEvent;
use Drupal\oe_corporate_countries\CorporateCountryRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Available countries event subscriber.
 */
class AvailableCountriesSubscriber implements EventSubscriberInterface {

  /**
   * The corporate country repository.
   *
   * @var \Drupal\oe_corporate_countries\CorporateCountryRepositoryInterface
   */
  protected $corporateCountryRepository;

  /**
   * Initialise a new instance of the subscriber.
   *
   * @param \Drupal\oe_corporate_countries\CorporateCountryRepositoryInterface $corporateCountryRepository
   *   The corporate country repository.
   */
  public function __construct(CorporateCountryRepositoryInterface $corporateCountryRepository) {
    $this->corporateCountryRepository = $corporateCountryRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      AddressEvents::AVAILABLE_COUNTRIES => ['removeDeprecatedCountries'],
    ];
  }

  /**
   * Removes deprecated countries from the available ones.
   *
   * @param \Drupal\address\Event\AvailableCountriesEvent $event
   *   The event.
   */
  public function removeDeprecatedCountries(AvailableCountriesEvent $event) {
    $available_countries = $event->getAvailableCountries();

    // If no available countries are passed, default it to all available ones.
    if (empty($available_countries)) {
      $available_countries = array_column($this->corporateCountryRepository->getCountries(), 'alpha-2');
      $available_countries = array_combine($available_countries, $available_countries);
    }

    // Extract the alpha-2 of all deprecated countries.
    $deprecated_countries = array_column($this->corporateCountryRepository->getDeprecatedCountries(), 'alpha-2');
    $deprecated_countries = array_combine($deprecated_countries, $deprecated_countries);

    // Exclude all the deprecated countries from availability.
    $event->setAvailableCountries(array_diff($available_countries, $deprecated_countries));
  }

}
