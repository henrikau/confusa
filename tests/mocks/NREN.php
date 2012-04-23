<?php
class NREN
{
	private $name;
	private $country;
	function __construct($n = "Mock NREN", $c = "NO")
	{
		$this->name = $n;
		$this->country = $c;
	}
	function getName() { return $this->name; }
	function getCountry() { return $this->country; }
	function getID() { return 1; }
}
?>