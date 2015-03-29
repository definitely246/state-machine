<?php namespace StateMachine;

class Context
{
	/**
	 * Stores attributes
	 *
	 * @var array
	 */
	protected $attributes;

	/**
	 * Context is just a big ol' data storage
	 *
	 */
	public function __construct()
	{
		$this->attributes = array();
	}

	/**
	 * Magic getter
	 *
	 * @param  string $attribute
	 * @return object
	 */
	public function __get($attribute)
	{
		return array_key_exists($attribute, $this->attributes)
			? $this->attributes[$attribute]
			: null;
	}

	/**
	 * Magic setter
	 *
	 * @param string $attribute
	 * @param mixed  $value
	 */
	public function __set($attribute, $value)
	{
		$this->attributes[$attribute] = $value;
	}
}