<?php

class Subscriber
{
	private $idp;
	private $name;
	function __construct($i = "mock idp", $n = "MOCK UNIVERSITY icarus")
	{
		$this->idp  = $i;
		$this->name = $n;
	}
	function getIdPName() { return $this->idp; }
	function getOrgName() { return $this->name; }
}

class Person
{
	private $s;
	private $n;
	function __construct($name = "John Doe", $s = null, $n = null)
	{
		$this->s = $s;
		if (is_null($this->s))
			$this->s = new Subscriber();
		$this->n = $n;
		if (is_null($this->n))
			$this->n = new NREN();
		$this->name = $name;
	}
	function isAuth() { return true; }
	function getX509ValidCN() { return $this->name; }
	function getSubscriber() { return $this->s; }
	function getNREN() { return $this->n; }
}
?>