<?php

namespace CirrusSearch;

use BagOStuff;
use MediaWiki\Interwiki\InterwikiLookup;
use WANObjectCache;

/**
 * Factory class used to create InterwikiResolver
 */
class InterwikiResolverFactory {
	/**
	 * @const string service name used in MediaWikiServices
	 */
	public const SERVICE = 'CirrusSearchInterwikiResolverFactory';

	/**
	 * @return InterwikiResolverFactory
	 */
	public static function newFactory() {
		return new InterwikiResolverFactory();
	}

	/**
	 * Based on config variables available in $config
	 * returns the approriate the InterwikiResolver
	 * implementation.
	 * Fallback to EmptyInterwikiResolver.
	 *
	 * @param SearchConfig $config
	 * @param \MultiHttpClient|null $client http client to fetch cirrus config
	 * @param WANObjectCache|null $wanCache Cache object for caching repeated requests
	 * @param BagOStuff|null $srvCache Local server cache object for caching repeated requests
	 * @param InterwikiLookup|null $iwLookup
	 * @return InterwikiResolver
	 * @throws \Exception
	 * @see CirrusSearchInterwikiResolverFactory::accepts()
	 * @see SiteMatrixInterwikiResolver::accepts()
	 */
	public function getResolver(
		SearchConfig $config,
		\MultiHttpClient $client = null,
		WANObjectCache $wanCache = null,
		BagOStuff $srvCache = null,
		InterwikiLookup $iwLookup = null
	) {
		if ( CirrusConfigInterwikiResolver::accepts( $config ) ) {
			return new CirrusConfigInterwikiResolver( $config, $client, $wanCache, $srvCache, $iwLookup );
		}
		if ( SiteMatrixInterwikiResolver::accepts( $config ) ) {
			return new SiteMatrixInterwikiResolver( $config, $client, $wanCache, $srvCache );
		}
		return new EmptyInterwikiResolver();
	}
}
