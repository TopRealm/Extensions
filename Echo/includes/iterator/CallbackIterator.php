<?php

/**
 * Applies a callback to all values returned from the iterator
 */
class EchoCallbackIterator extends IteratorDecorator {
	/** @var callable */
	protected $callable;

	public function __construct( Iterator $iterator, $callable ) {
		parent::__construct( $iterator );
		$this->callable = $callable;
	}

	public function current() {
		return call_user_func( $this->callable, $this->iterator->current() );
	}
}
