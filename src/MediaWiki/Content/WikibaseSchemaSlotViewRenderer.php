<?php

namespace Wikibase\Schema\MediaWiki\Content;

use Html;
use LanguageCode;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MediaWikiServices;
use MessageLocalizer;
use ParserOutput;
use SpecialPage;
use Title;
use Wikibase\Schema\MediaWiki\SpecificLanguageMessageLocalizer;
use Wikibase\Schema\Services\SchemaDispatcher\FullViewSchemaData;
use Wikibase\Schema\Services\SchemaDispatcher\NameBadge;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseSchemaSlotViewRenderer {

	/** @var MessageLocalizer */
	private $messageLocalizer;

	/** @var LinkRenderer */
	private $linkRenderer;

	/**
	 * @param string $languageCode The language in which to render the view.
	 */
	public function __construct( $languageCode, LinkRenderer $linkRenderer = null ) {
		$this->messageLocalizer = new SpecificLanguageMessageLocalizer( $languageCode );
		$this->linkRenderer = $linkRenderer ?: MediaWikiServices::getInstance()->getLinkRenderer();
	}

	private function msg( $key ) {
		return $this->messageLocalizer->msg( $key );
	}

	public function fillParserOutput(
		FullViewSchemaData $schemaData,
		Title $title,
		ParserOutput $output
	) {
		$output->addModuleStyles( 'ext.WikibaseSchema.view' );
		$output->setText(
			$this->renderNameBadges( $title, $schemaData->nameBadges ) .
			$this->renderSchemaSection( $title, $schemaData->schemaText )
		);
		$output->setDisplayTitle(
			$this->renderHeading( reset( $schemaData->nameBadges ), $title )
		);
	}

	private function renderNameBadges( Title $title, array $nameBadges ) {
		$html = Html::openElement( 'table', [ 'class' => 'wikitable' ] );
		$html .= $this->renderNameBadgeHeader();
		$html .= Html::openElement( 'tbody' );
		foreach ( $nameBadges as $langCode => $nameBadge ) {
			$html .= "\n";
			$html .= $this->renderNameBadge( $nameBadge, $langCode, $title->getText() );
		}
		$html .= Html::closeElement( 'tbody' );
		$html .= Html::closeElement( 'table' );
		return $html;
	}

	private function renderNameBadgeHeader() {
		$tableHeaders = '';
		// message keys:
		// wikibaseschema-namebadge-header-language-code
		// wikibaseschema-namebadge-header-label
		// wikibaseschema-namebadge-header-description
		// wikibaseschema-namebadge-header-aliases
		// wikibaseschema-namebadge-header-edit
		foreach ( [ 'language-code', 'label', 'description', 'aliases', 'edit' ] as $key ) {
			$tableHeaders .= Html::element(
				'th',
				[],
				$this->msg( 'wikibaseschema-namebadge-header-' . $key )
					->parse()
			);
		}

		return Html::rawElement( 'thead', [], Html::rawElement(
			'tr',
			[],
			$tableHeaders
		) );
	}

	private function renderNameBadge( NameBadge $nameBadge, $languageCode, $schemaId ) {
		$language = Html::element(
			'td',
			[],
			$languageCode
		);
		$bcp47 = LanguageCode::bcp47( $languageCode ); // 'simple' => 'en-simple' etc.
		$label = Html::element(
			'td',
			[
				'class' => 'wbschema-label',
				'lang' => $bcp47,
			],
			$nameBadge->label
		);
		$description = Html::element(
			'td',
			[
				'class' => 'wbschema-description',
				'lang' => $bcp47,
			],
			$nameBadge->description
		);
		$aliases = Html::element(
			'td',
			[
				'class' => 'wbschema-aliases',
				'lang' => $bcp47,
			],
			implode( ' | ', $nameBadge->aliases )
		);
		$editLink = $this->renderNameBadgeEditLink( $schemaId, $languageCode );
		return Html::rawElement(
			'tr',
			[],
			$language . $label . $description . $aliases . $editLink
		);
	}

	private function renderNameBadgeEditLink( $schemaId, $langCode ) {
		$specialPageTitleValue = SpecialPage::getTitleValueFor(
			'SetSchemaLabelDescriptionAliases',
			$schemaId . '/' . $langCode
		);

		return Html::rawElement(
			'td',
			[
				'class' => 'wbschema-edit-button',
			],
			$this->linkRenderer->makeLink(
				$specialPageTitleValue,
				$this->msg( 'wikibaseschema-edit' ),
				[ 'class' => 'edit-icon' ]
			)
		);
	}

	private function renderSchemaSection( Title $title, $schemaText ) {
		return Html::rawElement( 'div', [
			'id' => 'wbschema-schema-view-section',
			'class' => 'wbschema-section',
		],
			$this->renderSchemaTextLinks( $title ) .
			$this->renderSchemaText( $schemaText )
		);
	}

	private function renderSchemaText( $schemaText ) {
		return Html::element(
			'pre',
			[
				'id' => 'wbschema-schema-text',
				'class' => 'wbschema-schema-text',
			],
			$schemaText
		);
	}

	private function renderSchemaTextLinks( Title $title ) {
		return Html::rawElement(
			'div',
			[
				'class' => 'wbschema-schema-text-links',
			],
			$this->renderSchemaEditLink( $title )
		);
	}

	private function renderSchemaEditLink( Title $title ) {
		return Html::rawElement(
			'span',
			[
				'id' => 'wbschema-edit-schema-text',
				'class' => 'wbschema-edit-button',
			],
			$this->linkRenderer->makeLink(
				$title,
				$this->msg( 'wikibaseschema-edit' ),
				[ 'class' => 'edit-icon' ],
				[ 'action' => 'edit' ]
			)
		);
	}

	private function renderHeading( NameBadge $nameBadge, Title $title ) {
		if ( $nameBadge->label !== '' ) {
			$label = Html::element(
				'span',
				[ 'class' => 'wbschema-title-label' ],
				$nameBadge->label
			);
		} else {
			$label = Html::element(
				'span',
				[ 'class' => 'wbschema-title-label-empty' ],
				$this->msg( 'wikibaseschema-label-empty' )
					->text()
			);
		}

		$id = Html::element(
			'span',
			[ 'class' => 'wbschema-title-id' ],
			$this->msg( 'parentheses' )
				->plaintextParams( $title->getText() )
				->text()
		);

		return Html::rawElement(
			'span',
			[ 'class' => 'wbschema-title' ],
			$label . ' ' . $id
		);
	}

}