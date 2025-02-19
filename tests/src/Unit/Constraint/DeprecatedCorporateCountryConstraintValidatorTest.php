<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_corporate_countries\Unit\Constraint;

use Drupal\oe_corporate_countries\CorporateCountryRepositoryInterface;
use Drupal\oe_corporate_countries\Plugin\Validation\Constraint\DeprecatedCorporateCountryConstraint;
use Drupal\oe_corporate_countries\Plugin\Validation\Constraint\DeprecatedCorporateCountryConstraintValidator;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Tests the deprecated corporate country constraint validator.
 *
 * @package oe_corporate_countries
 */
class DeprecatedCorporateCountryConstraintValidatorTest extends UnitTestCase {

  /**
   * Tests the validation method.
   *
   * @param string $value
   *   The value to validate.
   * @param array|null $country_data
   *   The country data returned by the repository.
   * @param bool $violation_expected
   *   TRUE when a violation is expected, FALSE otherwise.
   *
   * @dataProvider validateDataProvider
   */
  public function testValidate(string $value, ?array $country_data, bool $violation_expected): void {
    $constraint = new DeprecatedCorporateCountryConstraint();

    $context = $this->createMock(ExecutionContextInterface::class);
    if ($violation_expected) {
      $context
        ->expects($this->once())
        ->method('addViolation')
        ->with($constraint->message, ['%value' => $value]);
    }
    else {
      $context
        ->expects($this->never())
        ->method('addViolation');
    }

    $corporate_country_repository = $this->createMock(CorporateCountryRepositoryInterface::class);
    $corporate_country_repository
      ->expects($this->once())
      ->method('getCountryByIsoAlpha2')
      ->with($value)
      ->willReturn($country_data);

    $validator = new DeprecatedCorporateCountryConstraintValidator($corporate_country_repository);
    $validator->initialize($context);
    $validator->validate($value, $constraint);
  }

  /**
   * Tests the validator with "not validable" values.
   *
   * These values cause an early exit of the validation.
   *
   * @param string|null $value
   *   The value to pass to the validation.
   *
   * @dataProvider validationSkippedDataProvider
   */
  public function testValidationSkipped(?string $value): void {
    $constraint = new DeprecatedCorporateCountryConstraint();
    $context = $this->createMock(ExecutionContextInterface::class);
    $context
      ->expects($this->never())
      ->method('addViolation');

    $corporate_country_repository = $this->createMock(CorporateCountryRepositoryInterface::class);
    $corporate_country_repository
      ->expects($this->never())
      ->method('getCountryByIsoAlpha2');

    $validator = new DeprecatedCorporateCountryConstraintValidator($corporate_country_repository);
    $validator->initialize($context);
    $validator->validate($value, $constraint);
  }

  /**
   * Data provider for the testValidate() method.
   *
   * @return array
   *   A list of test case data.
   */
  public static function validateDataProvider(): array {
    return [
      'deprecated country' => [
        'DC',
        ['deprecated' => TRUE],
        TRUE,
      ],
      'valid country' => [
        'VC',
        ['deprecated' => FALSE],
        FALSE,
      ],
      'country not found' => [
        'NF',
        NULL,
        FALSE,
      ],
    ];
  }

  /**
   * Data provider for the testValidationSkipped() test.
   *
   * @return array
   *   A list of test case data.
   */
  public static function validationSkippedDataProvider(): array {
    return [
      'null value' => [NULL],
      'empty string value' => [''],
    ];
  }

}
