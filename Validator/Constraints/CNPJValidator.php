<?php
namespace Mero\Bundle\BaseBundle\Validator\Constraints;

use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @package Mero\Bundle\BaseBundle\Validator\Constraints
 * @author Rafael Mello <merorafael@gmail.com>
 * @api
 */
class CNPJValidator extends ConstraintValidator
{

    /**
     * @inheritDoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof CNPJ)
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\CNPJ');
        if (!empty($value)) {
            $value = preg_replace('/[^0-9]/', '', $value);
            if (strlen($value) != 14) {
                $this->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->addViolation();
                return;
            }
            for ($i = 0, $aux = 5, $count = 0; $i < 12; $i++) {
                $count += $value{$i}*$aux;
                $aux = ($aux == 2)
                    ? 9
                    : $aux - 1;
            }
            $d1 = $count % 11;
            $d1 = $d1 < 2
                ? 0
                : 11 - $d1;
            if ($value{12} != $d1) {
                $this->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->addViolation();
                return;
            }
            for ($i = 0, $aux = 6, $count = 0; $i < 13; $i++) {
                $count += $value{$i}*$aux;
                $aux = ($aux == 2)
                    ? 9
                    : $aux - 1;
            }
            $d2 = $count % 11;
            $d2 = $d2 < 2
                ? 0
                : 11 - $d2;
            if ($value{13} != $d2) {
                $this->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->addViolation();
                return;
            }
        }
    }

}
