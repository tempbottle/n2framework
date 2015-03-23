<?php

	namespace N2f;

	/**
	 * Node for generating some types of files.
	 *
	 * Node that generates certain types of files
	 * based on the CLI arguments provided.
	 *
	 * @version 1.0
	 * @author Andrew Male
	 * @copyright 2014-2015 Zibings.com
	 * @package N2F
	 */
	class CoreGenerate extends NodeBase {
		public function __construct() {
			$this->SetKey('N2f-CoreGenerate')->SetVersion('1.0');

			return;
		}

		public function Process($Sender, DispatchBase &$Dispatch) {
			if (!($Dispatch instanceof GenerateDispatch)) {
				return;
			}

			/** @var GenerateDispatch $Dispatch */

			$Fh = $Dispatch->GetFileHelper();
			$EntityType = $Dispatch->GetEntityType();
			$Params = $Dispatch->GetAssocParameters();

			if (($EntityType != "ext" && $EntityType != "extension") || !array_key_exists('name', $Params)) {
				return;
			}

			$ext = $Params['name'];
			$Dispatch->Consume();

			if ($Fh->FolderExists("~N2f/Extensions/{$ext}")) {
				$Dispatch->SetResult("Extension folder already exists, could not generate extension.");

				return;
			}

			$config = array(
				'name' => '',
				'author' => '',
				'version' => ''
			);

			$Ch = $Dispatch->GetConsoleHelper();

			$Ch->PutLine();
			$Ch->PutLine("Answer the following questions to configure your new extension.");
			$Ch->PutLine();

			while (true) {
				$Ch->Put("Extension Name [{$Params['name']}]: ");
				$Name = $Ch->GetLine();

				if (!empty($Name)) {
					if (!$Fh->FolderExists("~N2f/Extensions/{$Name}")) {
						$config['name'] = $this->EscapePhpString($Name);

						break;
					} else {
						$Ch->PutLine("Extension already exists, please try another name.");
					}
				} else {
					$config['name'] = $this->EscapePhpString($Params['name']);

					break;
				}
			}

			while (true) {
				$Ch->Put("Extension Author: ");
				$Author = $Ch->GetLine();

				if (!empty($Author)) {
					$config['author'] = $this->EscapePhpString($Author);

					break;
				} else {
					$Ch->PutLine("Invalid author name!");
				}
			}

			while (true) {
				$Ch->Put("Extension Version: ");
				$Version = $Ch->GetLine();

				if (!empty($Version)) {
					$config['version'] = $this->EscapePhpString($Version);

					break;
				} else {
					$Ch->PutLine("Invalid version number.");
				}
			}

			$Jh = new JsonHelper();
			$Ch->PutLine();

			if ($Fh->MakeFolder("~N2f/Extensions/{$ext}")) {
				$Fh->PutContents("~N2f/Extensions/{$ext}/{$ext}.cfg", $Jh->EncodePretty($config));

				$ExtFile = str_replace('%EXTENSION%', $ext, $Fh->GetContents("~N2f/Core/Templates/Extension/Extension.ext.php"));
				$Fh->PutContents("~N2f/Extensions/{$ext}/{$ext}.ext.php", $ExtFile);
				$Fh->PutContents("~N2f/Extensions/{$ext}/index.php", "<?" . "php ?" . ">");

				$Ch->PutLine("Successfully generated base files for {$ext} extension.");

				if ($Ch->HasArg('enable')) {
					$Cfg = $Jh->DecodeAssoc($Fh->GetContents("~N2f/Includes/N2f.cfg"));

					if (isset($Cfg['extensions'])) {
						$found = false;

						foreach (array_values($Cfg['extensions']) as $e) {
							if ($e == $ext) {
								$found = true;

								break;
							}
						}

						if (!$found) {
							$Cfg['extensions'][] = $ext;
						}
					} else {
						$Cfg['extensions'] = array($ext);
					}

					$Fh->PutContents("~N2f/Includes/N2f.cfg", $Jh->EncodePretty($Cfg));
					$Ch->PutLine("Extension {$ext} enabled in N2f.cfg.");
				}
			} else {
				$Dispatch->SetResult("Failed to create extension directory, check filesystem permissions.");
			}

			return;
		}

		protected function EscapePhpString($Str) {
			return str_replace(array('"', "'"), array('\"', "\'"), $Str);
		}
	}

?>