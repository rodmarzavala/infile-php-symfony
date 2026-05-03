# Infile PHP Symfony Bundle

[![Packagist Version](https://img.shields.io/packagist/v/rodmarzavala/infile-php-symfony)](https://packagist.org/packages/rodmarzavala/infile-php-symfony)
[![PHP Version Require](https://img.shields.io/packagist/php-v/rodmarzavala/infile-php-symfony)](https://packagist.org/packages/rodmarzavala/infile-php-symfony)
[![License](https://img.shields.io/packagist/l/rodmarzavala/infile-php-symfony)](https://packagist.org/packages/rodmarzavala/infile-php-symfony)

The official Symfony bundle for the `infile-php` Guatemala FEL (Factura Electrónica en Línea) SDK.
Features zero-config integration, Messenger support, and a dedicated Profiler DataCollector.

> **Note:** This repository is a read-only split of the main `infile-php` monorepo. Please submit issues and pull requests to the [main repository](https://github.com/rodmarzavala/infile-php).

## Installation

```bash
composer require rodmarzavala/infile-php-symfony
```

## Documentation

For full documentation, configuration options, and API reference, please visit our official documentation site:

**👉 [Official Documentation (rodmarzavala.github.io/infile-php)](https://rodmarzavala.github.io/infile-php/)**

## Usage Example

Inject the `InfileService` into your controllers or services:

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use InfilePhp\Symfony\Service\InfileService;
use InfilePhp\Core\Dte\Invoice;
use InfilePhp\Core\Dte\Recipient;
use InfilePhp\Core\Dte\Item;

class InvoiceController extends AbstractController
{
    #[Route('/invoice', name: 'app_invoice')]
    public function issue(InfileService $infile): Response
    {
        $response = $infile->issue(
            Invoice::create()
                ->for(Recipient::withTaxId('12345678')->name('Juan Pérez')->address('Ciudad'))
                ->add(Item::product('Laptop')->quantity(1)->unitPrice(8500.00))
        );

        return new Response('Issued DTE: ' . $response->uuid());
    }
}
```
