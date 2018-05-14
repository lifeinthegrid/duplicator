<?php
defined("ABSPATH") or die("");

class DUP_MU
{

    public static function isMultisite()
    {
        return self::getMode() > 0;
    }

    // 0 = single site; 1 = multisite subdomain; 2 = multisite subdirectory
    public static function getMode()
    {

		if(is_multisite()) {
            if (defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL) {
                return 1;
            } else {
                return 2;
            }
        } else {
            return 0;
        }
    }

    public static function getGeneration()
    {
        if(self::getMode() == 0)
        {
            return DUP_PRO_MU_Generations::NotMultisite;
        }
        else
        {
			$sitesDir = WP_CONTENT_DIR . '/uploads/sites';

			if(file_exists($sitesDir))
            {
				return DUP_PRO_MU_Generations::ThreeFivePlus;
            }
            else
            {
				return DUP_PRO_MU_Generations::PreThreeFive;
            }
        }
    }
}