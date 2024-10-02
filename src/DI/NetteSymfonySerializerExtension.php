<?php

declare(strict_types = 1);

namespace QaData\NetteSymfonySerializer\DI;

use Doctrine\Common\Annotations\AnnotationReader;
use Nette;
use stdClass;
use Symfony\Component\Serializer;

/** @method stdClass getConfig() */
final class NetteSymfonySerializerExtension extends Nette\DI\CompilerExtension
{

	public const Serializer = 'symfony.serializer';

	public const ValidatorBuilder = 'symfony.serializer.builder';

	public const AttributeLoader = 'symfony.serializer.attribute.loader';

	public const ClassMetadataFactory = 'symfony.serializer.classMetadataFactory';

	public const ObjectNormalizer = 'symfony.serializer.objectNormalizer';

	public const AnnotationReader = 'doctrine.annotationReader';

	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Nette\Schema\Expect::structure([
			'cache' => Nette\Schema\Expect::structure([
				'directory' => Nette\Schema\Expect::string('../temp/cache'),
				'lifetime' => Nette\Schema\Expect::int(0),
				'namespace' => Nette\Schema\Expect::string('serializer.cache'),
			]),
			'objectNormalizer' => Nette\Schema\Expect::structure([
				'propertyTypeExtractor' => Nette\Schema\Expect::type('string|' . Nette\DI\Definitions\Statement::class)
					->default('Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor'),
				'nameConverter' => Nette\Schema\Expect::type('string|' . Nette\DI\Definitions\Statement::class)
					->nullable()
					->default(null),
			]),
			'normalizers' => Nette\Schema\Expect::arrayOf(
				Nette\Schema\Expect::anyOf(
					Nette\Schema\Expect::string(),
					Nette\Schema\Expect::type(Nette\DI\Definitions\Statement::class)
				)
			)->default([
				new Nette\DI\Definitions\Statement(Serializer\Normalizer\DateTimeNormalizer::class),
				new Nette\DI\Definitions\Statement(Serializer\Normalizer\ArrayDenormalizer::class),
			]),
			'encoders' => Nette\Schema\Expect::arrayOf(
				Nette\Schema\Expect::anyOf(
					Nette\Schema\Expect::string(),
					Nette\Schema\Expect::type(Nette\DI\Definitions\Statement::class)
				)
			)->default([
				new Nette\DI\Definitions\Statement(Serializer\Encoder\JsonEncoder::class),
			]),
			'lifetime' => Nette\Schema\Expect::int(0),
			'namespace' => Nette\Schema\Expect::string('serializer.cache'),
		]);
	}

	public function loadConfiguration(): void
	{
		$config = $this->getConfig();

		$builder = $this->getContainerBuilder();

		// Register annotation reader if not already registered
		if ($builder->hasDefinition(self::AnnotationReader) === false) {
			$builder->addDefinition(self::AnnotationReader)
				->setType(AnnotationReader::class)
				->setAutowired(false);

		}

		// Register attribute loader
		$attributeLoaderDef = $builder->addDefinition(self::AttributeLoader)
			->setType(Serializer\Mapping\Loader\AttributeLoader::class)
			->setFactory(Serializer\Mapping\Loader\AttributeLoader::class, [
				new Nette\DI\Definitions\Statement('Doctrine\Common\Annotations\PsrCachedReader', [
					sprintf('@%s', self::AnnotationReader),
					new Nette\DI\Definitions\Statement('Symfony\Component\Cache\Adapter\FilesystemAdapter', [
						$config->cache->namespace,
						$config->cache->lifetime,
						$config->cache->directory,
					]),
				]),
			])
			->setAutowired(false);

		// Register class metadata factory
		$classMetadataFactoryDef = $builder->addDefinition(self::ClassMetadataFactory)
			->setFactory(Serializer\Mapping\Factory\ClassMetadataFactory::class, [
				$attributeLoaderDef,
			])
			->setAutowired(false);

		// Register object normalizer
		$builder->addDefinition(self::ObjectNormalizer)
			->setType(Serializer\Normalizer\ObjectNormalizer::class)
			->setFactory(Serializer\Normalizer\ObjectNormalizer::class, [
				'classMetadataFactory' => $classMetadataFactoryDef,
				'propertyTypeExtractor' => new Nette\DI\Definitions\Statement($this->config->objectNormalizer->propertyTypeExtractor),
				'nameConverter' => $this->config->objectNormalizer->nameConverter
					? new Nette\DI\Definitions\Statement($this->config->objectNormalizer->nameConverter)
					: null,
			])
			->setAutowired(false);

		// Register serializer
		$builder->addDefinition(self::Serializer)
			->setType(Serializer\Serializer::class)
			->setFactory(Serializer\Serializer::class, [
				'normalizers' => $config->normalizers,
				'encoders' => $config->encoders,
			]);

	}

}
