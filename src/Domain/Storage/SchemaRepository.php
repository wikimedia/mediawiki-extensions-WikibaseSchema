<?php

namespace Wikibase\Schema\Domain\Storage;

use Wikibase\Schema\Domain\Model\Schema;

/**
 * @license GPL-2.0-or-later
 */
interface SchemaRepository {

	/**
	 * @param Schema $schema
	 *
	 * @return string
	 */
	public function storeSchema( Schema $schema );

}