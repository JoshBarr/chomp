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

        $args = explode("/", $args);

        $last = end(array_values($args));

        if (sizeof($args == 2) && Common::startsWith($args[0], "block-")) {
            // $arg_str = implode("/", $args);
            return $app->redirect("/project/$client/$project/");
        }

        // if (preg_match('/^\d+$/', $last) == 0) {
        //     $args[] = 1;
        //     $arg_str = implode("/", $args);
        //     $arg_str = str_replace("//", "/", $arg_str);
        //     return $app->redirect("/project/$client/$project/$arg_str");
        // }

        print_r($args);

        $relpath = $client . "/" . $project . "/" . $dir;

        $sl = new ImageList($app['config']['clients'] . "/" . $relpath);
        $images = $sl->ls();

        return $app['twig']->render("screenshot.twig", array(
            'name' => $client,
            'project' => $project,
            'image' => $sl->image_for($screenshot-1),
            'images' => $images,
            'relpath' => $relpath
        ));
        
        return "foo";
    }
    
    public function redirect(Request $request, Application $app, $client, $project) {
        return $app->redirect("/project/$client/$project/");
    }
}


/*


$app->get('/project/{client}/{project}/', function ($client, $project)  use ($app) {

    if (!protectUrl($app, $client)) {
        return $app->redirect('/login');
    }

    $clientObject = new Springload\ClientProjectList($app['config']['clients'], $client);
    $projectObject = new Springload\ClientProject($clientObject, $project);

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
})
->assert('client', '^(?!admin|edit).*$');



// Redirect attempts to access the images directories to the first image instead

// $app->get('/project/{client}/{project}/{dir}/', function($client, $project, $dir) use ($app) {
//     return $app->redirect("/project/" . $client . "/" . $project . "/" . $dir . "/1");
// })
// ->assert('client', '^(?!admin|edit).*$');

// ----------------------------------------------------------------------------
// Image viewing methods
// ----------------------------------------------------------------------------

$app->get('/project/{client}/{project}/{args}', function ($client, $project, $args) use ( $app ) {

    if (!protectUrl($app, $client)) {
        return $app->redirect('/login');
    }



    print_r($args);



    $relpath = $client . "/" . $project . "/" . $dir;

    $sl = new Springload\ImageList(__DIR__ . "/" . $app['config']['clients_path'] . "/" . $relpath);
    $images = $sl->ls();

    return $app['twig']->render("screenshot.twig", array(
        'name' => $client,
        'project' => $project,
        'image' => $sl->image_for($screenshot-1),
        'images' => $images,
        'relpath' => $relpath
    ));
})

 */