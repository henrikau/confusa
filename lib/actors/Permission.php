<?php
/**
 * Permission - store if the user has permission to conduct a certain portal
 * operation here.
 *
 * This is more a convenience class, so both the permission to perform a certain
 * operation and the reason why or why not that may be done can be passed around
 * using one object.
 *
 * The goal is to get a more reliable method for that than to return a
 * notification string in the negative case and test whether that is null.
 *
 * @Author	Thomas Zangerl <tzangerl@pdc.kth.se>
 * @license	http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 * @since	File available since Confusa v0.3.1
 * @package	resources
 */
class Permission
{
	/* is the permission for the current operation granted? */
	private $permissionGranted;
	/* reasons for a denied permission */
	private $reasons;

	public function __construct()
	{
		/* default the permission to false */
		$this->permissionGranted = false;
		$reasons = array();
	}

	public function setPermission($perm = false)
	{
		$this->permissionGranted = $perm;
	}

	public function isPermissionGranted()
	{
		return $this->permissionGranted;
	}

	public function addReason($reason)
	{
		$this->reasons[] = $reason;
	}

	public function getReasons()
	{
		return $reasons;
	}

	/**
	 * Get the reasons why or why not the operation is permitted in a HTML
	 * string for user reporting.
	 */
	public function getFormattedReasons()
	{
		$formattedReasons = "<ul>";
		foreach($this->reasons as $reason) {
			$formattedReasons .= "<li style=\"margin-bottom: 10px\">" .
					htmlentities($reason) . "</li>";
		}
		$formattedReasons .= "</ul>";
		return $formattedReasons;
	}

	public function getStringReasons()
	{
		$stringReasons = "";

		foreach ($this->reasons as $reason) {
			$stringReasons .= $reason . "\n";
		}

		return $stringReasons;
	}
}

?>
