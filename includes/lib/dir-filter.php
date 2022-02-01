<?php


class WPS_Dir_Filter extends \RecursiveFilterIterator
{
	protected $exclude;

	public function __construct($iterator, array $exclude)
	{
		parent::__construct($iterator);
		$this->exclude = $exclude;
	}

	public function accept()
	{
		return !($this->isDir() && in_array($this->getFilename(), $this->exclude));
	}

	public function getChildren()
	{
		return new WPS_Dir_Filter($this->getInnerIterator()->getChildren(), $this->exclude);
	}
}
