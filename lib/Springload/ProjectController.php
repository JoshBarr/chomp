<?php

namespace Springload;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Springload\Common;
use Springload\ClientProjectList;
use Springload\ClientProject;
use Springload\ImageList;




class ProjectController 
{
    public function index(Request $request, Application $app, $client, $project)
    {
        
        if (!protectUrl($app, $client)) {
            return $app->redirect('/login');
        }

        $clientObject = new ClientProjectList($app['config']['clients'], $client);
        $projectObject = new ClientProject($clientObject, $project);

        $edit_url = false;

        if ($app['security']->isGranted('ROLE_ADMIN')) {
            $edit_url = "/edit/$client/$project/";
        }

        return $app['twig']->render("project.twig", array(
            'name' => $client,
            // 'project' => $project,
            'edit' => $edit_url,
            'project' => $projectObject->getData(),
            'client' => $clientObject->getData()
        ));
    }

    public function sequence(Request $request, Application $app, $client, $project, $args)
    {
        if (!protectUrl($app, $client)) {
            return $app->redirect('/login');
        }

        $argsArray = explode("/", $args);
        $last = end(array_values($argsArray));


        if (sizeof($argsArray) < 2) {
            if (Common::startsWith($argsArray[0], "block-")) {
                return $app->redirect("/project/$client/$project/");
            } else {
                return $app->redirect("/project/$client/$project/$argsArray[0]/1");
            }
        }

        /**
         * Redirect empty requests back to the project index
         */
        if ($argsArray[1] == "") {
            return $app->redirect("/project/$client/$project/");
        }

        if (sizeof($argsArray) == 2 && Common::startsWith($argsArray[0], "block-")) {
            $argsArray[] = "1";
            $arg_str = implode("/", $argsArray);
            return $app->redirect("/project/$client/$project/$arg_str");
        }

        $index = array_pop($argsArray);
        $relpath = $client . "/" . $project . "/" . implode("/", $argsArray);

        $sl = new ImageList($app['config']['clients'] . "/" . $relpath);
        $images = $sl->ls();

        return $app['twig']->render("screenshot.twig", array(
            'name' => $client,
            'project' => $project,
            'image' => $sl->image_for($index-1),
            'images' => $images,
            'relpath' => $relpath
        ));
        
    }
    
    public function redirect(Request $request, Application $app, $client, $project) {
        return $app->redirect("/project/$client/$project/");
    }
}
