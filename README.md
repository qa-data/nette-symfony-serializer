# NETTE Symfony Serializer

Integrates Symfony Serializer into Nette Framework.

## Setup

NETTE Symfony Serializer is available on composer:

```bash
composer require qa-data/nette-symfony-serializer
```

At first register compiler extension.

```neon
extensions:
    symfonyValidator: QaData\NetteSymfonySerializer\DI\NetteSymfonySerializerExtension
```

## Configuration

```neon
symfonySerializer:
    normalizers:
        # You can add your own normalizers
        - Symfony\Component\Serializer\Normalizer\DateTimeNormalizer()
        - Symfony\Component\Serializer\Normalizer\ArrayDenormalizer()
        - Symfony\Component\Serializer\Normalizer\ObjectNormalizer()
    encoders:
        - Symfony\Component\Serializer\Encoder\JsonEncoder()
    objectNormalizer:
        propertyTypeExtractor: Symfony\Component\PropertyInfo\PropertyTypeExtractor\PropertyTypeExtractor()
        nameConverter: Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter()
    cache: # optional
        directory: %tempDir%/cache/validator
        lifetime: 0
        namespace: validator.cache
```
