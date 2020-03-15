<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Core\Bridge\Symfony\Routing;

use ApiPlatform\Core\Api\IdentifiersExtractor;
use ApiPlatform\Core\Api\IdentifiersExtractorInterface;
use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Api\OperationType;
use ApiPlatform\Core\Api\ResourceClassResolverInterface;
use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\OperationDataProviderTrait;
use ApiPlatform\Core\DataProvider\SubresourceDataProviderInterface;
use ApiPlatform\Core\Exception\InvalidArgumentException;
use ApiPlatform\Core\Exception\InvalidIdentifierException;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Identifier\IdentifierConverterInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Property\Factory\PropertyNameCollectionFactoryInterface;
use ApiPlatform\Core\Util\AttributesExtractor;
use ApiPlatform\Core\Util\ResourceClassInfoTrait;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingExceptionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * {@inheritdoc}
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class IriConverter implements IriConverterInterface
{
    use ClassInfoTrait;
    use OperationDataProviderTrait;

    private $decorated;
    private $routeNameResolver;
    private $router;
    private $identifiersExtractor;

    public function __construct(
        IriConverterInterface $decorated,
        RouteNameResolverInterface $routeNameResolver,
        RouterInterface $router,
        IdentifiersExtractorInterface $identifiersExtractor = null
    )
    {
        $this->decorated = $decorated;
        $this->routeNameResolver = $routeNameResolver;
        $this->router = $router;
        $this->identifiersExtractor = $identifiersExtractor;
    }

    public function getItemFromIri(string $iri, array $context = [])
    {
        return $this->decorated->getItemFromIri($iri, $context);
    }

    public function getIriFromItem($item, int $referenceType = UrlGeneratorInterface::ABS_PATH): string
    {
        $resourceClass = $this->getObjectClass($item);
        $routeName = $this->routeNameResolver->getRouteName($resourceClass, OperationType::ITEM);

        if($resourceClass === User::class){
            $routeName = 'api_users_get_item';
        }

        try {
            $identifiers = $this->generateIdentifiersUrl($this->identifiersExtractor->getIdentifiersFromItem($item), $resourceClass);

            return $this->router->generate($routeName, ['id' => implode(';', $identifiers)], $referenceType);
        } catch (RuntimeException $e) {
            throw new InvalidArgumentException(sprintf(
                'Unable to generate an IRI for the item of type "%s"',
                $resourceClass
            ), $e->getCode(), $e);
        } catch (RoutingExceptionInterface $e) {
            throw new InvalidArgumentException(sprintf(
                'Unable to generate an IRI for the item of type "%s"',
                $resourceClass
            ), $e->getCode(), $e);
        }
    }

    private function generateIdentifiersUrl(array $identifiers, string $resourceClass): array
    {
        if (0 === \count($identifiers)) {
            throw new InvalidArgumentException(sprintf(
                'No identifiers defined for resource of type "%s"',
                $resourceClass
            ));
        }

        if (1 === \count($identifiers)) {
            return [rawurlencode((string) reset($identifiers))];
        }

        foreach ($identifiers as $name => $value) {
            $identifiers[$name] = sprintf('%s=%s', $name, $value);
        }

        return array_values($identifiers);
    }

    public function getIriFromResourceClass(string $resourceClass, int $referenceType = UrlGeneratorInterface::ABS_PATH): string
    {
        return $this->decorated->getItemIriFromResourceClass($resourceClass, $referenceType);
    }

    public function getItemIriFromResourceClass(string $resourceClass, array $identifiers, int $referenceType = UrlGeneratorInterface::ABS_PATH): string
    {
        return $this->decorated->getItemIriFromResourceClass($resourceClass, $identifiers, $referenceType);
    }

    public function getSubresourceIriFromResourceClass(string $resourceClass, array $identifiers, int $referenceType = UrlGeneratorInterface::ABS_PATH): string
    {
        return $this->decorated->getSubresourceIriFromResourceClass($resourceClass, $identifiers, $referenceType);
    }
}
