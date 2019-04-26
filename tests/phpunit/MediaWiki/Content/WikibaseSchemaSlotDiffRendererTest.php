<?php

namespace EntitySchema\Tests\MediaWiki\Content;

use PHPUnit\Framework\TestCase;
use RequestContext;
use TextSlotDiffRenderer;
use EntitySchema\MediaWiki\Content\WikibaseSchemaContent;
use EntitySchema\MediaWiki\Content\WikibaseSchemaSlotDiffRenderer;

/**
 * @license GPL-2.0-or-later
 * @covers \EntitySchema\MediaWiki\Content\WikibaseSchemaSlotDiffRenderer
 */
class WikibaseSchemaSlotDiffRendererTest extends TestCase {

	public function diffDataProvider() {

		yield 'blank' => [
			[],
			[],
			'',
		];

		$schemaEn = [
			'labels' => [
				'en' => 'test label',
			],
			'descriptions' => [
				'en' => 'test description',
			],
			'aliases' => [
				'en' => [ 'test alias', 'test alias 2' ],
			],
			'schemaText' => 'test schema',
			'type' => 'ShExC',
			'serializationVersion' => '3.0',
		];

		yield 'no change' => [
			$schemaEn,
			$schemaEn,
			'',
		];

		yield 'change from empty schema counts as addition (not change)' => [
			[ 'schemaText' => '', 'serializationVersion' => '3.0' ],
			[ 'schemaText' => 'test schema', 'serializationVersion' => '3.0' ],
			// phpcs:disable Generic.Files.LineLength.MaxExceeded
			'<tr><td colspan="2" class="diff-lineno"></td><td colspan="2" class="diff-lineno">Schema</td></tr><tr><td colspan="2"></td><td class="diff-marker">+</td><td class="diff-addedline">test schema</td></tr>',
			// phpcs:enable
		];

		yield 'change to empty schema counts as removal (not change)' => [
			[ 'schemaText' => 'test schema', 'serializationVersion' => '3.0' ],
			[ 'schemaText' => '', 'serializationVersion' => '3.0' ],
			// phpcs:disable Generic.Files.LineLength.MaxExceeded
			'<tr><td colspan="2" class="diff-lineno">Schema</td><td colspan="2" class="diff-lineno"></td></tr><tr><td class="diff-marker">−</td><td class="diff-deletedline">test schema</td><td colspan="2"></td></tr>',
			// phpcs:enable
		];

		yield 'add label' => [
			[],
			[
				'labels' => [
					'en' => 'testlabel',
				],
				'serializationVersion' => '3.0',
			],
			// phpcs:disable Generic.Files.LineLength.MaxExceeded
			'<tr><td colspan="2" class="diff-lineno"></td><td colspan="2" class="diff-lineno">Labels / en</td></tr><tr><td colspan="2"></td><td class="diff-marker">+</td><td class="diff-addedline">testlabel</td></tr>'
			// phpcs:enable
		];

		// phpcs:disable Generic.Files.LineLength.TooLong
		$expectedHTML = <<<HTML
<tr>
	<td colspan="2" class="diff-lineno">Labels / en</td>
	<td colspan="2" class="diff-lineno">Labels / en</td>
</tr>
<tr>
	<td class="diff-marker">−</td>
	<td class="diff-deletedline">test label</td>
	<td class="diff-marker">+</td>
	<td class="diff-addedline">updated label</td>
</tr>
<tr>
	<td colspan="2" class="diff-lineno">Descriptions / en</td>
	<td colspan="2" class="diff-lineno"></td>
</tr>
<tr>
	<td class="diff-marker">−</td>
	<td class="diff-deletedline">test description</td>
	<td colspan="2"></td>
</tr>
<tr>
	<td colspan="2" class="diff-lineno"></td>
	<td colspan="2" class="diff-lineno">Descriptions / de</td>
</tr>
<tr>
	<td colspan="2"></td>
	<td class="diff-marker">+</td>
	<td class="diff-addedline">Testbeschreibung</td>
</tr>
<tr>
	<td colspan="2" class="diff-lineno"></td>
	<td colspan="2" class="diff-lineno">Aliases / en / 0</td>
</tr>
<tr>
	<td colspan="2"></td>
	<td class="diff-marker">+</td><td class="diff-addedline">test alias 3</td>
</tr>
<tr>
	<td colspan="2" class="diff-lineno">Aliases / en / 1</td>
	<td colspan="2" class="diff-lineno"></td>
</tr>
<tr>
	<td class="diff-marker">−</td>
	<td class="diff-deletedline">test alias 2</td>
	<td colspan="2"></td>
</tr>
<tr>
	<td colspan="2" class="diff-lineno"></td>
	<td colspan="2" class="diff-lineno">Aliases / de / 0</td>
</tr>
<tr>
	<td colspan="2"></td>
	<td class="diff-marker">+</td>
	<td class="diff-addedline">Testalias</td>
</tr>
<tr>
	<td colspan="2" class="diff-lineno" id="mw-diff-left-l1" >Schema / <!--LINE 1--></td>
	<td colspan="2" class="diff-lineno">Schema / <!--LINE 1--></td>
</tr>
<tr>
	<td class='diff-marker'>−</td>
	<td class='diff-deletedline'><div><del class="diffchange diffchange-inline">test </del>schema</div></td>
	<td class='diff-marker'>+</td>
	<td class='diff-addedline'><div>schema <ins class="diffchange diffchange-inline">updated</ins></div></td>
</tr>
HTML;
		// phpcs:enable

		yield 'changes, removals and additions' => [
			$schemaEn,
			[
				'labels' => [
					'en' => 'updated label',
				],
				'descriptions' => [
					'de' => 'Testbeschreibung',
				],
				'aliases' => [
					'en' => [ 'test alias', 'test alias 3' ],
					'de' => [ 'Testalias' ],
				],
				'schemaText' => 'schema updated',
				'serializationVersion' => '3.0',
			],
			$expectedHTML,
		];
	}

	/**
	 * @dataProvider diffDataProvider
	 */
	public function testGetDiff( $oldSchema, $newSchema, $expectedHTML ) {
		$oldContent = new WikibaseSchemaContent( json_encode( $oldSchema ) );
		$newContent = new WikibaseSchemaContent( json_encode( $newSchema ) );
		$context = RequestContext::getMain();
		$textSlotDiffRenderer = new TextSlotDiffRenderer();
		$textSlotDiffRenderer->setEngine( TextSlotDiffRenderer::ENGINE_PHP );
		$diffRenderer = new WikibaseSchemaSlotDiffRenderer(
			$context,
			$textSlotDiffRenderer
		);

		$diff = $diffRenderer->getDiff( $oldContent, $newContent );

		$this->assertXmlStringEqualsXmlString(
			'<table>' . $expectedHTML . '</table>',
			'<table>' . $diff . '</table>'
		);
	}

}
