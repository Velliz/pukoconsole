<?php

namespace pukoconsole;

/**
 * Class Seeds
 * @package pukoconsole
 *
 * A old fashioned nginx based multiple app style
 *
 */
class Seeds
{

    public function __construct()
    {
        echo "Puko Framework Material Development Tool";
        echo "\n";
        echo "app name : ";
        $app = preg_replace('/\s+/', '', fgets(STDIN));
        echo "app type [api or reg] : ";
        $type = preg_replace('/\s+/', '', fgets(STDIN));

        $dir = sprintf('apps/%s', $app);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            if ($type === 'reg') {
                mkdir(sprintf("%s/assets/html", $dir), 0777, true);
                mkdir(sprintf("%s/assets/html/id", $dir), 0777, true);
                mkdir(sprintf("%s/assets/html/en", $dir), 0777, true);
                mkdir(sprintf("%s/assets/master", $dir), 0777, true);
                mkdir(sprintf("%s/assets/system", $dir), 0777, true);
            }
            mkdir(sprintf("%s/config", $dir), 0777, true);
            mkdir(sprintf("%s/controller", $dir), 0777, true);
        } else {
            echo "\n";
            echo "Aborting: apps with name %s already created!";
            echo "\n";
            exit();
        }
        $index = file_get_contents("template/index");
        $index = str_replace("{{apps}}", $app, $index);
        file_put_contents(sprintf("%s/index.php", $dir), $index);

        $routes = file_get_contents("template/routes");
        file_put_contents(sprintf("%s/routes.php", $dir), $routes);

        $puko = file_get_contents("template/puko");
        $puko = str_replace("{{apps}}", $app, $puko);
        file_put_contents(sprintf("%s/puko", $dir), $puko);

        $apache = file_get_contents("template/.htaccess");
        file_put_contents(sprintf("%s/.htaccess", $dir), $apache);

        $database = file_get_contents("template/config/database");
        file_put_contents(sprintf("%s/config/database.php", $dir), $database);

        $appf = file_get_contents("template/config/app");
        file_put_contents(sprintf("%s/config/app.php", $dir), $appf);

        $encryption = file_get_contents("template/config/encryption");
        file_put_contents(sprintf("%s/config/encryption.php", $dir), $encryption);

        $routes = file_get_contents("template/config/routes");
        file_put_contents(sprintf("%s/config/routes.php", $dir), $routes);

        if ($type === 'reg') {
            $master = file_get_contents("template/assets/master");
            file_put_contents(sprintf("%s/assets/master/master.html", $dir), $master);

            $auth = file_get_contents("template/assets/system/auth");
            file_put_contents(sprintf("%s/assets/system/auth.html", $dir), $auth);

            $error = file_get_contents("template/assets/system/error");
            file_put_contents(sprintf("%s/assets/system/error.html", $dir), $error);

            $exception = file_get_contents("template/assets/system/exception");
            file_put_contents(sprintf("%s/assets/system/exception.html", $dir), $exception);

            $permission = file_get_contents("template/assets/system/permission");
            file_put_contents(sprintf("%s/assets/system/permission.html", $dir), $permission);

            $construction = file_get_contents("template/assets/system/construction");
            file_put_contents(sprintf("%s/assets/system/construction.html", $dir), $construction);
        }

        echo "\n";
        echo sprintf("Success: %s app with name %s created!", $type, $app);
        echo "\n";
    }
}