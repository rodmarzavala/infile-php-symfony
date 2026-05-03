# Infile PHP Symfony Bundle

[![Packagist Version](https://img.shields.io/packagist/v/rodmarzavala/infile-php-symfony)](https://packagist.org/packages/rodmarzavala/infile-php-symfony)
[![PHP Version Require](https://img.shields.io/packagist/php-v/rodmarzavala/infile-php-symfony)](https://packagist.org/packages/rodmarzavala/infile-php-symfony)
[![License](https://img.shields.io/packagist/l/rodmarzavala/infile-php-symfony)](https://packagist.org/packages/rodmarzavala/infile-php-symfony)

El bundle nativo para Symfony del SDK `infile-php` de facturación electrónica en línea (FEL) para Guatemala.
Incluye integración *zero-config*, soporte para Symfony Messenger y un *DataCollector* dedicado en el Profiler.

> **Nota:** Este repositorio es una división de solo lectura (read-only split) del monorepo principal `infile-php`. Por favor, envía tus *issues* y *pull requests* al [repositorio principal](https://github.com/rodmarzavala/infile-php).

## Instalación

```bash
composer require rodmarzavala/infile-php-symfony
```

## Documentación

Para acceder a la documentación completa, opciones de configuración y referencia de la API, por favor visita nuestro sitio:

**👉 [Documentación del SDK (rodmarzavala.github.io/infile-php)](https://rodmarzavala.github.io/infile-php/)**

## Ejemplo de Uso

Inyecta el `InfileService` en tus controladores o servicios:

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

        return new Response('DTE Emitido con UUID: ' . $response->uuid());
    }
}
```
