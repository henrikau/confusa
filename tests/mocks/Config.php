<?php
class Config
{
	static function get_config($key)
	{
		switch($key)
		{
		case 'cert_product':
			// PRD_ESCIENCE : 0
			// PRD_PERSONAL : 1
			return 0;
		case 'ca_mode':
			/* CA_STANDALONE : 0 */
			/* CA_COMODO : 1 */
			return 1;

		case 'custom_mail_tpl':
			return "/data/web/confusa/custom_tpl/";
		case 'install_path':
			return dirname(dirname(dirname(__FILE__)));
		case 'server_url':
			return "localhost";

		case 'sys_from_address':
		case 'sys_header_from_address':
			return "conf@localhost";

		case 'system_name':
			return "confusa devel";

		default:
			echo __FILE__ . " key not found: $key\n";
			return "";
		}
	}
}
?>