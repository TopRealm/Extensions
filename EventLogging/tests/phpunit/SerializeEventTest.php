<?php
/**
 * PHP Unit tests for serializeEvent function
 *
 * @file
 * @ingroup Extensions
 *
 * @author Nuria Ruiz <nuria@wikimedia.org>
 */

use MediaWiki\Extension\EventLogging\EventLogging;

/**
 * @group EventLogging
 * @covers \MediaWiki\Extension\EventLogging\EventLogging::serializeEvent
 */
class SerializeEventTest extends MediaWikiIntegrationTestCase {

	/**
	 * Empty event should be returned as an object.
	 */
	public function testSerializeEventEmptyEvent() {
		$encapsulatedEvent = [
			'event'            => [],
			'other'            => 'some',
		];
		$expectedJson = "{\"event\":{},\"other\":\"some\"}";
		$json = EventLogging::serializeEvent( $encapsulatedEvent );
		$this->assertEquals( $expectedJson, $json,
			'Empty event should be returned as an object' );
	}

	/**
	 * Event should be returned without modifications
	 */
	public function testSerializeEventHappyCase() {
		$event = [];
		$event['prop1'] = 'blah';
		$encapsulatedEvent = [
			'event'            => $event,
			'other'            => 'some',
		];
		$expectedJson = "{\"event\":{\"prop1\":\"blah\"},\"other\":\"some\"}";
		$json = EventLogging::serializeEvent( $encapsulatedEvent );
		$this->assertEquals( $expectedJson, $json,
			'Event should be a simple json string' );
	}
}
