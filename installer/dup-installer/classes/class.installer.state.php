<?php
defined("ABSPATH") or die("");

abstract class DUPX_InstallerMode
{
	const Unknown = -1;
    const StandardInstall = 0;
    const OverwriteInstall = 1;
}

class DUPX_InstallerState
{
	const State_Filename = 'installer-state.txt';
    public $mode = DUPX_InstallerMode::Unknown;
	public $ovr_wp_content_dir = '';
	//public $isManualExtraction = false;

    private static $state_filepath = null;

	private static $instance = null;

    public static function init($clearState) {
        self::$state_filepath = dirname(__FILE__).'/../installer-state.txt';

        if($clearState) {
            SnapLibIOU::rm(self::$state_filepath);
        }
    }

	public static function getInstance($init_state = false)
	{
		if($init_state) {
			self::$instance = null;
			if(file_exists(self::$state_filepath)) {
				unlink(self::$state_filepath);
			}
		}

		// Still using an installer state file since will be stuff we want to retain between steps at some point but for now it just checks wp-config.php
		if (self::$instance == null) {

			self::$instance = new DUPX_InstallerState();

			if (file_exists(self::$state_filepath)) {

				$file_contents = file_get_contents(self::$state_filepath);
				$data = json_decode($file_contents);

				foreach ($data as $key => $value) {
					self::$instance->{$key} = $value;
				}
            } else {

				$wpConfigPath	= "{$GLOBALS['DUPX_ROOT']}/wp-config.php";

				if(file_exists($wpConfigPath)) {
					$defines = DUPX_WPConfig::parseDefines($wpConfigPath);
					
					self::$instance->mode = DUPX_InstallerMode::OverwriteInstall;
					self::$instance->ovr_wp_content_dir = SnapLibUtil::getArrayValue($defines, 'WP_CONTENT_DIR', false, $GLOBALS['CURRENT_ROOT_PATH'] . '/wp-content');

				} else {
					self::$instance->mode = DUPX_InstallerMode::StandardInstall;
				}
			}


			self::$instance->save();
		}

		return self::$instance;
	}

    public function save()
    {
		$data = SnapLibStringU::jsonEncode($this);

        SnapLibIOU::filePutContents(self::$state_filepath, $data);
    }
}

DUPX_InstallerState::init($GLOBALS['INIT']);