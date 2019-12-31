<?php

/**
 * @see       https://github.com/mezzio/mezzio-hal for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-hal/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-hal/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Hal\ResourceGenerator;

use Mezzio\Hal\HalResource;
use Mezzio\Hal\Link;
use Mezzio\Hal\Metadata;
use Mezzio\Hal\ResourceGenerator;
use Psr\Http\Message\ServerRequestInterface;
use Traversable;

use function get_class;
use function http_build_query;
use function parse_str;
use function parse_url;
use function preg_replace;
use function sprintf;
use function str_replace;

use const PHP_URL_FRAGMENT;
use const PHP_URL_QUERY;

class UrlBasedCollectionStrategy implements StrategyInterface
{
    use ExtractCollectionTrait;

    public function createResource(
        $instance,
        Metadata\AbstractMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : HalResource {
        if (! $metadata instanceof Metadata\UrlBasedCollectionMetadata) {
            throw Exception\UnexpectedMetadataTypeException::forMetadata(
                $metadata,
                self::class,
                Metadata\UrlBasedCollectionMetadata::class
            );
        }

        if (! $instance instanceof Traversable) {
            throw Exception\InvalidCollectionException::fromInstance($instance, get_class($this));
        }

        return $this->extractCollection($instance, $metadata, $resourceGenerator, $request);
    }

    /**
     * @param string $rel Relation to use when creating Link
     * @param int $page Page number for generated link
     * @param Metadata\AbstractCollectionMetadata $metadata Used to provide the
     *     base URL, pagination parameter, and type of pagination used (query
     *     string, path parameter)
     * @param ResourceGenerator $resourceGenerator Ignored; required to fulfill
     *     abstract.
     * @param ServerRequestInterface $request Ignored; required to fulfill
     *     abstract.
     * @return Link
     */
    protected function generateLinkForPage(
        string $rel,
        int $page,
        Metadata\AbstractCollectionMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) : Link {
        $paginationParam = $metadata->getPaginationParam();
        $paginationType = $metadata->getPaginationParamType();
        $url = $metadata->getUrl();

        switch ($paginationType) {
            case Metadata\AbstractCollectionMetadata::TYPE_PLACEHOLDER:
                $url = str_replace($url, $paginationParam, $page);
                break;
            case Metadata\AbstractCollectionMetadata::TYPE_QUERY:
                // fall-through
            default:
                $url = $this->stripUrlFragment($url);
                $url = $this->appendPageQueryToUrl($url, $page, $paginationParam);
        }

        return new Link($rel, $url);
    }

    /**
     * @param Metadata\AbstractCollectionMetadata $metadata Provides base URL
     *     for self link.
     * @param ResourceGenerator $resourceGenerator Ignored; required to fulfill
     *     abstract.
     * @param ServerRequestInterface $request Ignored; required to fulfill
     *     abstract.
     * @return Link
     */
    protected function generateSelfLink(
        Metadata\AbstractCollectionMetadata $metadata,
        ResourceGenerator $resourceGenerator,
        ServerRequestInterface $request
    ) {
        return new Link('self', $metadata->getUrl());
    }

    private function stripUrlFragment(string $url) : string
    {
        $fragment = parse_url($url, PHP_URL_FRAGMENT);
        if (null === $fragment) {
            // parse_url returns null both for absence of fragment and empty fragment
            return preg_replace('/#$/', '', $url);
        }

        return str_replace('#' . $fragment, '', $url);
    }

    private function appendPageQueryToUrl(string $url, int $page, string $paginationParam) : string
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (null === $query) {
            // parse_url returns null both for absence of query and empty query
            $url = preg_replace('/\?$/', '', $url);
            return sprintf('%s?%s=%s', $url, $paginationParam, $page);
        }

        parse_str($query, $qsa);
        $qsa[$paginationParam] = $page;

        return str_replace('?' . $query, '?' . http_build_query($qsa), $url);
    }
}
