<?php
/**
 * Print query results in interactive timelines.
 *
 * @file SRF_Timeline.php
 * @ingroup SemanticResultFormats
 *
 * @author Markus Krötzsch
 *
 * FIXME: this code is just insane; rewrite from 0 is probably the only way to get it right
 */

/**
 * Result printer for timeline data.
 *
 * @ingroup SemanticResultFormats
 */
class SRFTimeline extends SMWResultPrinter {

	protected $m_tlstart = '';  // name of the start-date property if any
	protected $m_tlend = '';  // name of the end-date property if any
	protected $m_tlsize = ''; // CSS-compatible size (such as 400px)
	protected $m_tlbands = ''; // array of band IDs (MONTH, YEAR, ...)
	protected $m_tlpos = ''; // position identifier (start, end, today, middle)
	protected $mTemplate;
	protected $mNamedArgs;

	/**
	 * @see SMWResultPrinter::handleParameters
	 *
	 * @since 1.6.3
	 *
	 * @param array $params
	 * @param $outputmode
	 */
	protected function handleParameters( array $params, $outputmode ) {
		parent::handleParameters( $params, $outputmode );

		$this->mTemplate = trim( $params['template'] );
		$this->mNamedArgs = $params['named args'];
		$this->m_tlstart = smwfNormalTitleDBKey( $params['timelinestart'] );
		$this->m_tlend = smwfNormalTitleDBKey( $params['timelineend'] );
		$this->m_tlbands = $params['timelinebands'];
		$this->m_tlpos = strtolower( trim( $params['timelineposition'] ) );

		// str_replace makes sure this is only one value, not mutliple CSS fields (prevent CSS attacks)
		// / FIXME: this is either unsafe or redundant, since Timeline is Wiki-compatible. If the JavaScript makes user inputs to CSS then it is bad even if we block this injection path.
		$this->m_tlsize = htmlspecialchars( str_replace( ';', ' ', strtolower( $params['timelinesize'] ) ) );
	}

	public function getName() {
		// Give grep a chance to find the usages:
		// srf_printername_timeline, srf_printername_eventline
		return wfMessage( 'srf_printername_' . $this->mFormat )->text();
	}

	protected function getResultText( SMWQueryResult $res, $outputmode ) {
		SMWOutputs::requireHeadItem( SMW_HEADER_STYLE );
		SMWOutputs::requireResource( 'ext.srf.timeline' );

		$isEventline = 'eventline' == $this->mFormat;
		$id = uniqid();

		if ( !$isEventline && ( $this->m_tlstart == '' ) ) { // seek defaults
			foreach ( $res->getPrintRequests() as $pr ) {
				if ( ( $pr->getMode() == SMWPrintRequest::PRINT_PROP ) && ( $pr->getTypeID() == '_dat' ) ) {
					$dataValue = $pr->getData();

					$date_value = $dataValue->getDataItem()->getLabel();

					if ( ( $this->m_tlend == '' ) && ( $this->m_tlstart != '' ) &&
						( $this->m_tlstart != $date_value ) ) {
						$this->m_tlend = $date_value;
					} elseif ( ( $this->m_tlstart == '' ) && ( $this->m_tlend != $date_value ) ) {
						$this->m_tlstart = $date_value;
					}
				}
			}
		}

		// print header
		$result = "<div id=\"smwtimeline-$id\" class=\"smwtimeline is-disabled\" style=\"height: $this->m_tlsize\">";
		$result .= '<span class="smw-overlay-spinner medium" style="top:40%; transform: translate(-50%, -50%);"></span>';

		foreach ( $this->m_tlbands as $band ) {
			$result .= '<span class="smwtlband" style="display:none;">' . htmlspecialchars( $band ) . '</span>';
			// just print any "band" given, the JavaScript will figure out what to make of it
		}

		// print all result rows
		if ( ( $this->m_tlstart != '' ) || $isEventline ) {
			$result .= $this->getEventsHTML( $res, $outputmode, $isEventline );
		}
		// no further results displayed ...

		// print footer
		$result .= '</div>';

		// yes, our code can be viewed as HTML if requested, no more parsing needed
		$this->isHTML = $outputmode == SMW_OUTPUT_HTML;

		return $result;
	}

	/**
	 * Returns the HTML for the events.
	 *
	 * @since 1.5.3
	 *
	 * @param SMWQueryResult $res
	 * @param $outputmode
	 * @param bool $isEventline
	 *
	 * @return string
	 */
	protected function getEventsHTML( SMWQueryResult $res, $outputmode, $isEventline ) {
		global $curarticle, $cururl; // why not, code flow has reached max insanity already

		$positions = []; // possible positions, collected to select one for centering
		$curcolor = 0; // color cycling is used for eventline

		$result = '';

		$output = false; // true if output for the popup was given on current line
		if ( $isEventline ) {
			$events = [];
		} // array of events that are to be printed

		while ( $row = $res->getNext() ) { // Loop over the objcts (pages)
			$hastime = false; // true as soon as some startdate value was found
			$hastitle = false; // true as soon as some label for the event was found
			$curdata = ''; // current *inner* print data (within some event span)
			$curmeta = ''; // current event meta data
			$cururl = '';
			$curarticle = ''; // label of current article, if it was found; needed only for eventline labeling
			$first_col = true;

			if ( $this->mTemplate != '' ) {
				$this->hasTemplates = true;
				$template_text = '';
				$i = 0;
			}

			foreach ( $row as $field ) { // Loop over the returned properties
				$first_value = true;
				$pr = $field->getPrintRequest();
				$dataValue = $pr->getData();

				if ( $dataValue == '' ) {
					$date_value = null;
				} else {
					$date_value = $dataValue->getDataItem()->getLabel();
				}

				while ( ( $object = $field->getNextDataValue() ) !== false ) { // Loop over property values
					$event = $this->handlePropertyValue(
						$object,
						$outputmode,
						$pr,
						$first_col,
						$hastitle,
						$hastime,
						$first_value,
						$isEventline,
						$curmeta,
						$curdata,
						$date_value,
						$output,
						$positions
					);

					if ( $this->mTemplate != '' ) {
						$template_text .= '|' . ( $this->mNamedArgs ? '?' . $field->getPrintRequest()->getLabel(
								) : $i + 1 ) . '=';
						if ( !$first_value ) {
							$template_text .= ', ';
						}
						$template_text .= $object->getShortText( SMW_OUTPUT_WIKI, $this->getLinker( $first_value ) );
						$i++;
					}

					if ( $event !== false ) {
						$events[] = $event;
					}

					$first_value = false;
				}

				if ( $output ) {
					$curdata .= '<br />';
				}

				$output = false;
				$first_col = false;
			}

			if ( $this->mTemplate != '' ) {
				$curdata = '{{' . $this->mTemplate . $template_text . '}}';
			}

			if ( $hastime ) {
				$result .= Html::rawElement(
					'span',
					[ 'class' => 'smwtlevent', 'style' => 'display:none;' ],
					$curmeta . Html::element(
						'span',
						[ 'class' => 'smwtlcoloricon' ],
						$curcolor
					) . $curdata
				);
			}

			if ( $isEventline ) {
				foreach ( $events as $event ) {
					$result .= '<span class="smwtlevent" style="display:none;" ><span class="smwtlstart">' . $event[0] . '</span><span class="smwtlurl">' . $curarticle . '</span><span class="smwtlcoloricon">' . $curcolor . '</span>';
					if ( $curarticle != '' ) {
						$result .= '<span class="smwtlprefix">' . $curarticle . ' </span>';
					}
					$result .= $curdata . '</span>';
					$positions[$event[2]] = $event[0];
				}
				$events = [];
				$curcolor = ( $curcolor + 1 ) % 10;
			}
		}

		if ( count( $positions ) > 0 ) {
			ksort( $positions );
			$positions = array_values( $positions );

			switch ( $this->m_tlpos ) {
				case 'start':
					$result .= '<span class="smwtlposition" style="display:none;" >' . $positions[0] . '</span>';
					break;
				case 'end':
					$result .= '<span class="smwtlposition" style="display:none;" >' . $positions[count(
							$positions
						) - 1] . '</span>';
					break;
				case 'today':
					break; // default
				case 'middle':
				default:
					$result .= '<span class="smwtlposition" style="display:none;" >' . $positions[ceil(
							count( $positions ) / 2
						) - 1] . '</span>';
					break;
			}
		}

		return $result;
	}

	/**
	 * Hanldes a single property value. Returns an array with data for a single event or false.
	 *
	 * FIXME: 13 arguments, of which a whole bunch are byref... not a good design :)
	 *
	 * @since 1.5.3
	 *
	 * @param SMWDataValue $object
	 * @param $outputmode
	 * @param SMWPrintRequest $pr
	 * @param bool $first_col
	 * @param bool &$hastitle
	 * @param bool &$hastime
	 * @param bool $first_value
	 * @param bool $isEventline
	 * @param string &$curmeta
	 * @param string &$curdata
	 * @param $date_value
	 * @param bool &$output
	 * @param array &$positions
	 *
	 * @return false or array
	 */
	protected function handlePropertyValue( SMWDataValue $object, $outputmode, SMWPrintRequest $pr, $first_col,
		&$hastitle, &$hastime, $first_value, $isEventline, &$curmeta, &$curdata, $date_value, &$output, array &$positions ) {
		global $curarticle, $cururl;

		$event = false;

		$l = $this->getLinker( $first_col );

		if ( !$hastitle && $object->getTypeID(
			) != '_wpg' ) { // "linking" non-pages in title positions confuses timeline scripts, don't try this
			$l = null;
		}

		if ( $object->getTypeID() == '_wpg' ) { // use shorter "LongText" for wikipage
			$objectlabel = $object->getLongText( $outputmode, $l );
		} else {
			$objectlabel = $object->getShortText( $outputmode, $l );
		}

		$urlobject = ( $l !== null );
		$header = '';

		if ( $first_value ) {
			// find header for current value:
			if ( $this->mShowHeaders && ( '' != $pr->getLabel() ) ) {
				$header = $pr->getText( $outputmode, $this->mLinker ) . ': ';
			}

			// is this a start date?
			if ( ( $pr->getMode() == SMWPrintRequest::PRINT_PROP ) &&
				( $date_value == $this->m_tlstart ) ) {
				// FIXME: Timeline scripts should support XSD format explicitly. They
				// currently seem to implement iso8601 which deviates from XSD in cases.
				// NOTE: We can assume $object to be an SMWDataValue in this case.
				$curmeta .= Html::element(
					'span',
					[ 'class' => 'smwtlstart' ],
					$object->getXMLSchemaDate()
				);
				$positions[$object->getHash()] = $object->getXMLSchemaDate();
				$hastime = true;
			}

			// is this the end date?
			if ( ( $pr->getMode() == SMWPrintRequest::PRINT_PROP ) &&
				( $date_value == $this->m_tlend ) ) {
				// NOTE: We can assume $object to be an SMWDataValue in this case.
				$curmeta .= Html::element(
					'span',
					[ 'class' => 'smwtlend' ],
					$object->getXMLSchemaDate( false )
				);
			}

			// find title for displaying event
			if ( !$hastitle ) {
				$curmeta .= Html::rawElement(
					'span',
					[
						'class' => $urlobject ? 'smwtlurl' : 'smwtltitle'
					],
					$objectlabel
				);

				if ( $pr->getMode() == SMWPrintRequest::PRINT_THIS ) {
					$curarticle = $object->getLongText( $outputmode, $l );
					$cururl = $object->getDataItem()->getTitle()->getFullUrl();
				}

				// NOTE: type Title of $object implied
				$hastitle = true;
			}
		} elseif ( $output ) {
			// it *can* happen that output is false here, if the subject was not printed (fixed subject query) and mutliple items appear in the first row
			$curdata .= ', ';
		}

		if ( !$first_col || !$first_value || $isEventline ) {
			$curdata .= $header . $objectlabel;
			$output = true;
		}

		if ( $isEventline && ( $pr->getMode() == SMWPrintRequest::PRINT_PROP ) && ( $pr->getTypeID(
				) == '_dat' ) && ( '' != $pr->getLabel(
				) ) && ( $date_value != $this->m_tlstart ) && ( $date_value != $this->m_tlend ) ) {
			$event = [
				$object->getXMLSchemaDate(),
				$pr->getLabel(),
				$object->getDataItem()->getSortKey(),
			];
		}

		return $event;
	}

	/**
	 * @see SMWResultPrinter::getParamDefinitions
	 *
	 * @since 1.8
	 *
	 * @param $definitions array of IParamDefinition
	 *
	 * @return array of IParamDefinition|array
	 */
	public function getParamDefinitions( array $definitions ) {
		$params = parent::getParamDefinitions( $definitions );

		$params['timelinesize'] = [
			'default' => '300px',
			'message' => 'srf_paramdesc_timelinesize',
		];

		$params['timelineposition'] = [
			'default' => 'middle',
			'message' => 'srf_paramdesc_timelineposition',
			'values' => [ 'start', 'middle', 'end', 'today' ],
		];

		$params['timelinestart'] = [
			'default' => '',
			'message' => 'srf_paramdesc_timelinestart',
		];

		$params['timelineend'] = [
			'default' => '',
			'message' => 'srf_paramdesc_timelineend',
		];

		$params['timelinebands'] = [
			'islist' => true,
			'default' => [ 'MONTH', 'YEAR' ],
			'message' => 'srf_paramdesc_timelinebands',
			'values' => [ 'MINUTE', 'HOUR', 'DAY', 'WEEK', 'MONTH', 'YEAR', 'DECADE' ],
		];

		$params['template'] = [
			'message' => 'smw-paramdesc-template',
			'default' => '',
		];

		$params['named args'] = [
			'type' => 'boolean',
			'message' => 'smw-paramdesc-named_args',
			'default' => false,
		];

		return $params;
	}

}
