<?php

namespace bazilio\stubsgenerator;

use yii\console\Controller;
use yii\console\Exception;

class StubsController extends Controller
{
    public $outputFile = null;

    protected function getTemplate()
    {
        return <<<TPL
<?php

/**
 * Yii app stub file. Autogenerated by yii2-stubs-generator (stubs console command).
 * Used for enhanced IDE code autocompletion.
 * Updated on {time}
 */
class Yii extends \yii\BaseYii
{
    /**
     * @var BaseApplication|WebApplication|ConsoleApplication the application instance
     */
    public static \$app;
}
/**{stubs}
 **/
abstract class BaseApplication extends yii\base\Application
{
}

/**{stubs}
 **/
class WebApplication extends yii\web\Application
{
}

/**{stubs}
 **/
class ConsoleApplication extends yii\console\Application
{
}

/**
 * extends ide auto complete for Yii::\$app->user->identity to point to {userIdentityClass}
 *
 * @property {userIdentityClass} \$identity
 **/
class YiiUser extends {userClass}
{
}
TPL;
    }

    public function actionIndex($app)
    {
        $path = $this->outputFile ? $this->outputFile : \Yii::$app->getVendorPath() . DIRECTORY_SEPARATOR . 'Yii.php';

        $components = [];
		$userIdentityClass = 'app\models\User';
		$userClass = 'yii\web\User';

        foreach (\Yii::$app->requestedParams as $configFile) {

            if (!is_file($configFile)) {
                throw new Exception('Config file doesn\'t exists: ' . $configFile);
            }

            $config = include($configFile);

            foreach ($config['components'] as $name => $component) {
				if ($name == 'user') {
					if (isset($component['identityClass'])) {
						$userIdentityClass = $component['identityClass'];
					}
					if (isset($component['class'])) {
						$userClass = $component['class'];
					} else {
						$component['class'] = $userClass;
					}

				} else if (!isset($component['class'])) {
                    continue;
                }

                $components[$name][] = $component['class'];
            }
        }

		echo 'Creating Stubs file for '.count($components).' yii2\components'."\n";

        $stubs = '';
        foreach ($components as $name => $classes) {
            $classes = implode('|', array_unique($classes));
			if ($name == 'user') {
				$classes = 'YiiUser';
			}
            $stubs .= "\n * @property {$classes} \$$name";
        }

        $content = str_replace('{stubs}', $stubs, $this->getTemplate());
        $content = str_replace('{time}', date(DATE_ISO8601), $content);
		$content = str_replace('{userClass}', $userClass, $content);
		$content = str_replace('{userIdentityClass}', $userIdentityClass, $content);
		
		if (!is_file($path) || $content !== file_get_contents($path)) {
			file_put_contents($path, $content);
		}

		return 0;
    }
}
