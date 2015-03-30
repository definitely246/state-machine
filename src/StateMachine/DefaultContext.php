<?php namespace StateMachine;

class DefaultContext implements Context
{
	/**
	 * Stores attributes
	 *
	 * @var array
	 */
	protected $storage;

	/**
	 * Holds the current state for
	 * this context
	 *
	 * @var mixed
	 */
	protected $_state;

	/**
	 * Fully qualified namespace to resolve other objects
	 * that are passed in as strings
	 *
	 * @var string
	 */
	protected $_fully_qualified_namespace;

	/**
	 * Context is just a big ol' data storage
	 *
	 */
	public function __construct($fullQualifiedNamespace = '')
	{
		$this->_fully_qualified_namespace = $fullQualifiedNamespace;
		$this->storage = array();
	}

	/**
	 * Gets the current state
	 *
	 * @return object
	 */
	public function state()
	{
		return $this->_state;
	}

	/**
	 * Sets the current state
	 *
	 * @param   object
	 * @return  void
	 */
	public function setState($state)
	{
		$fqn = $this->_fully_qualified_namespace;

		$newState = is_string($state) ? ObjectResolverSingleton::make("{$fqn}\\{$state}", $this) : $state;

		$newState = $newState ?: ObjectResolverSingleton::make("{$state}", $this);

		$newState = $newState ?: $state;

		$this->_state = $newState;
	}

	/**
	 * Gets the attribute
	 *
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		if ($key === 'state') return $this->state();

		return array_key_exists($key, $this->storage)
			? $this->storage[$key]
			: $default;
	}

	/**
	 * Sets the attribute for us
	 *
	 * @param  string $key
	 * @param  mixed  $value
	 */
	public function set($key, $value)
	{
		$this->storage[$key] = $value;
	}

	/**
	 * Magic getter
	 *
	 * @param  string $key
	 * @return object
	 */
	public function __get($key)
	{
		return $this->get($key);
	}

	/**
	 * Magic setter
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	public function __set($key, $value)
	{
		return $this->set($key, $value);
	}
}