<?php
namespace Mero\Bundle\BaseBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 *
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Rafael Mello <merorafael@gmail.com>
 *
 * @api
 */
class BrazilianCNPJ extends Constraint
{

    public $message = "CNPJ entered is not valid";

}